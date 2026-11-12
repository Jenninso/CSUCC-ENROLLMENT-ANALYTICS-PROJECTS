<?php

namespace App\Http\Controllers;

use App\Models\SpesApplication;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use App\Services\SprPdf;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SpesApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = SpesApplication::query();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('work_period')) {
            $query->where('work_period', $request->query('work_period'));
        }

        if ($request->filled('application_year')) {
            $query->where('application_year', $request->integer('application_year'));
        }

        $summary = [
            'total' => (clone $query)->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'employed' => (clone $query)->where('status', 'employed')->count(),
        ];

        $applications = $query
            ->orderByDesc('application_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('spes.index', [
            'applications' => $applications,
            'summary' => $summary,
            'years' => SpesApplication::query()->select('application_year')->distinct()->orderByDesc('application_year')->pluck('application_year'),
        ]);
    }

    public function create()
    {
        return redirect()->route('spes.index')->with('error', 'Only the student can fill out an SPR. Open the saved record and allow one edit.');
    }

    public function store()
    {
        return redirect()->route('spes.index')->with('error', 'Only the student can fill out an SPR. Open the saved record and allow one edit.');
    }

    public function show(SpesApplication $spes)
    {
        $spes->load(['studentRecord.program', 'encoder']);

        return view('spes.show', ['application' => $spes]);
    }

    public function edit(SpesApplication $spes)
    {
        return redirect()->route('spes.show', $spes)->with('error', 'Staff cannot edit this SPR. Allow the student one edit instead.');
    }

    public function update(SpesApplication $spes)
    {
        return redirect()->route('spes.show', $spes)->with('error', 'Staff cannot edit this SPR. Allow the student one edit instead.');
    }

    public function updateStatus(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SpesApplication::STATUSES))],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $spes->update([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
        ]);
        $audit->log('update', 'spes', 'Updated office status for '.$spes->reference_no.' without changing the student answers.');

        return back()->with('success', 'Office status saved. The student answers were not changed.');
    }

    public function destroy(SpesApplication $spes, AuditLogger $audit)
    {
        $reference = $spes->reference_no;
        $spes->delete();
        $audit->log('delete', 'spes', 'Deleted SPR application '.$reference.'.');

        return redirect()->route('spes.index')->with('success', 'SPR application deleted.');
    }

    public function allowStudentEdit(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        $allow = $request->boolean('allow');
        $spes->update(['student_can_edit' => $allow]);
        $audit->log('update', 'spes', ($allow ? 'Allowed' : 'Locked').' student editing for '.$spes->reference_no.'.');

        return back()->with('success', $allow
            ? 'The student can edit this application once. It locks again after they save.'
            : 'Student editing is locked.');
    }

    public function print(SpesApplication $spes)
    {
        return view('spes.print', ['application' => $spes]);
    }

    public function pdf(SpesApplication $spes, SprPdf $pdf)
    {
        return $this->pdfResponse($pdf, $spes, false);
    }

    private function prefillFromStudent(int $studentId): array
    {
        if ($studentId < 1) {
            return [];
        }

        $record = StudentRecord::query()->with('program')->find($studentId);
        if (! $record) {
            return [];
        }

        return [
            'student_record_id' => $record->id,
            'student_id' => $record->student_id,
            'sex' => $record->gender,
            'education_level' => 'tertiary',
            'course' => $record->program?->program_name,
            'year_level' => SpesApplication::yearLevelFromStudent((int) $record->year_level),
            'general_average' => $record->average_grade,
            'enrollment_status' => $record->enrolled_status === 'enrolled' ? 'enrolled' : 'intending',
            'beneficiary_type' => 'student',
        ];
    }

    public function fields(Request $request, bool $asStudent = false, ?string $keepStatus = null, ?SpesApplication $existing = null): array
    {
        $levels = ['post_graduate', 'graduate', 'undergraduate', 'certificate'];
        $types = ['new_student', 'transferee', 'old_student', 'returnee'];
        $tracks = ['gas', 'humss', 'stem', 'abm', 'ict', 'he', 'agri', 'industrial', 'arts', 'sports', 'na'];
        $officeKeys = ['csu_at_total', 'csu_at_english', 'csu_at_math', 'csu_at_science', 'csu_at_logical', 'office_course_1', 'office_course_2', 'office_course_3', 'admission_year', 'admission_test_date'];

        $data = $request->validate([
            'student_record_id' => ['nullable', 'integer', 'exists:student_records,id'],
            'id_no' => ['nullable', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'student_level' => ['nullable', Rule::in($levels)],
            'admission_type' => ['nullable', Rule::in($types)],
            'birth_date' => ['required', 'date', 'before:today'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'civil_status' => ['nullable', 'string', 'max:20'],
            'sex_at_birth' => ['nullable', 'string', 'max:20'],
            'sogie' => ['nullable', 'string', 'max:80'],
            'place_of_birth' => ['nullable', 'string', 'max:150'],
            'citizenship' => ['nullable', 'string', 'max:80'],
            'religion' => ['nullable', 'string', 'max:80'],
            'home_purok' => ['nullable', 'string', 'max:120'],
            'home_barangay' => ['nullable', 'string', 'max:100'],
            'home_city' => ['nullable', 'string', 'max:100'],
            'home_province' => ['nullable', 'string', 'max:100'],
            'home_zip' => ['nullable', 'string', 'max:10'],
            'current_purok' => ['nullable', 'string', 'max:120'],
            'current_barangay' => ['nullable', 'string', 'max:100'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'current_province' => ['nullable', 'string', 'max:100'],
            'current_zip' => ['nullable', 'string', 'max:10'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'ethnicity' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:150'],
            'first_in_family' => ['nullable', Rule::in(['yes', 'no'])],
            'preferred_hand' => ['nullable', Rule::in(['right', 'left', 'both'])],
            'special_needs' => ['nullable', Rule::in(['yes', 'no'])],
            'special_needs_detail' => ['nullable', 'string', 'max:180'],
            'four_ps' => ['nullable', Rule::in(['yes', 'no'])],
            'household_id' => ['nullable', 'string', 'max:40'],
            'solo_parent' => ['nullable', Rule::in(['yes', 'no'])],
            'number_of_children' => ['nullable', 'integer', 'min:0', 'max:30'],
            'living_with_parents' => ['nullable', Rule::in(['yes', 'no'])],
            'father_name' => ['nullable', 'string', 'max:150'],
            'father_status' => ['nullable', Rule::in(['living', 'deceased'])],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'mother_status' => ['nullable', Rule::in(['living', 'deceased'])],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'estimated_income' => ['nullable', 'string', 'max:80'],
            'support' => ['nullable', 'array'],
            'support.*' => [Rule::in(['parents', 'guardian', 'student_assistant', 'employed', 'scholarship', 'others'])],
            'employed_time' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'scholarship_specify' => ['nullable', 'string', 'max:150'],
            'others_specify' => ['nullable', 'string', 'max:150'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'relationship' => ['nullable', 'string', 'max:80'],
            'guardian_mobile' => ['nullable', 'string', 'max:30'],
            'guardian_address' => ['nullable', 'string', 'max:255'],
            'elementary_school' => ['nullable', 'string', 'max:180'],
            'elementary_address' => ['nullable', 'string', 'max:180'],
            'elementary_sy' => ['nullable', 'string', 'max:40'],
            'secondary_school' => ['nullable', 'string', 'max:180'],
            'secondary_address' => ['nullable', 'string', 'max:180'],
            'secondary_sy' => ['nullable', 'string', 'max:40'],
            'last_school' => ['nullable', 'string', 'max:180'],
            'last_school_address' => ['nullable', 'string', 'max:180'],
            'last_school_years' => ['nullable', 'string', 'max:40'],
            'shs_track' => ['nullable', Rule::in($tracks)],
            'baccalaureate_degree' => ['nullable', 'string', 'max:180'],
            'baccalaureate_school' => ['nullable', 'string', 'max:180'],
            'baccalaureate_address' => ['nullable', 'string', 'max:180'],
            'baccalaureate_years' => ['nullable', 'string', 'max:40'],
            'preferred_campus' => ['nullable', Rule::in(['main', 'cabadbaran'])],
            'priority_1' => ['nullable', 'string', 'max:150'],
            'priority_2' => ['nullable', 'string', 'max:150'],
            'priority_3' => ['nullable', 'string', 'max:150'],
            'signature_name' => ['required', 'string', 'max:150'],
            'certified' => ['accepted'],
            'status' => ['required', Rule::in(array_keys(SpesApplication::STATUSES))],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'csu_at_total' => ['nullable', 'string', 'max:20'],
            'csu_at_english' => ['nullable', 'string', 'max:20'],
            'csu_at_math' => ['nullable', 'string', 'max:20'],
            'csu_at_science' => ['nullable', 'string', 'max:20'],
            'csu_at_logical' => ['nullable', 'string', 'max:20'],
            'office_course_1' => ['nullable', 'string', 'max:150'],
            'office_course_2' => ['nullable', 'string', 'max:150'],
            'office_course_3' => ['nullable', 'string', 'max:150'],
            'admission_year' => ['nullable', 'string', 'max:20'],
            'admission_test_date' => ['nullable', 'date'],
        ]);

        unset($data['certified']);
        $spr = $data;
        unset($spr['student_record_id'], $spr['status'], $spr['remarks']);
        if ($asStudent) {
            foreach (array_merge($officeKeys, ['remarks']) as $key) {
                unset($spr[$key]);
            }
            foreach ($officeKeys as $key) {
                if (array_key_exists($key, $existing->spr_data ?? [])) {
                    $spr[$key] = $existing->spr_data[$key];
                }
            }
        }

        $blank = fn ($value) => ($value === null || $value === '') ? 'N/A' : $value;
        $row = [
            'student_record_id' => ($data['student_record_id'] ?? null) ?: null,
            'student_id' => trim((string) ($data['id_no'] ?? '')) ?: null,
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'sex' => $blank($data['sex_at_birth'] ?? null),
            'civil_status' => $blank($data['civil_status'] ?? null),
            'birth_date' => $data['birth_date'],
            'age' => $request->filled('age') ? (int) $request->input('age') : $request->date('birth_date')->age,
            'place_of_birth' => $blank($data['place_of_birth'] ?? null),
            'address' => $blank($data['home_purok'] ?? null),
            'barangay' => $blank($data['home_barangay'] ?? null),
            'city_municipality' => $blank($data['home_city'] ?? null),
            'province' => $blank($data['home_province'] ?? null),
            'zip_code' => $data['home_zip'] ?? null,
            'contact_number' => $blank($data['mobile'] ?? null),
            'email' => $data['email'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'father_occupation' => $data['father_occupation'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'parents_annual_income' => 0,
            'number_of_siblings' => (int) ($data['number_of_children'] ?? 0),
            'education_level' => $data['student_level'] ?? 'undergraduate',
            'school_name' => $blank($data['secondary_school'] ?? ($data['elementary_school'] ?? null)),
            'school_address' => $data['secondary_address'] ?? null,
            'course' => $data['priority_1'] ?? null,
            'year_level' => 'N/A',
            'enrollment_status' => $data['admission_type'] ?? 'new_student',
            'application_year' => (int) now()->year,
            'work_period' => 'na',
            'preferred_days' => 0,
            'beneficiary_type' => 'student',
            'previous_spes' => false,
            'application_date' => now()->toDateString(),
            'applicant_signature' => $data['signature_name'],
            'status' => $data['status'] ?? 'submitted',
            'remarks' => $asStudent ? null : ($data['remarks'] ?? null),
            'spr_data' => $spr,
        ];

        if ($asStudent && $request->user()) {
            $studentId = $request->user()->studentNumber();
            $row['student_id'] = $studentId;
            $row['spr_data']['id_no'] = $studentId;
            $row['status'] = $keepStatus ?: 'submitted';
            $row['student_record_id'] = StudentRecord::query()
                ->where('student_id', $studentId)
                ->latest('id')
                ->value('id');
        } elseif ($asStudent) {
            $row['status'] = $keepStatus ?: 'submitted';
            $row['user_id'] = null;
        }

        return $row;
    }

    public function publicCreate()
    {
        return view('spes.public', ['application' => null]);
    }

    public function publicStore(Request $request, AuditLogger $audit)
    {
        $data = $this->fields($request, true, 'submitted');
        $data['reference_no'] = 'SPR-PENDING';
        $data['user_id'] = null;
        $data['encoded_by'] = null;
        $data['public_token'] = Str::random(40);
        $data['student_can_edit'] = false;

        $application = SpesApplication::create($data);
        $application->update([
            'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
        ]);

        $audit->log('create', 'spes', 'A student without an account submitted '.$application->reference_no.'.');

        return redirect()
            ->route('spr.public.edit', $application->public_token)
            ->with('success', 'Your SPR was saved. You cannot change it after saving. Reference '.$application->reference_no.'.');
    }

    public function publicEdit(string $token)
    {
        $application = $this->publicRecord($token);

        return view('spes.public', [
            'application' => $application,
            'locked' => true,
        ]);
    }

    public function publicUpdate(string $token)
    {
        $this->publicRecord($token);

        return redirect()
            ->route('spr.public.edit', $token)
            ->with('error', 'This form cannot be edited after it is saved.');
    }

    private function publicRecord(string $token): SpesApplication
    {
        return SpesApplication::query()->where('public_token', $token)->firstOrFail();
    }

    public function studentIndex(Request $request)
    {
        $applications = SpesApplication::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(10);

        return view('student.spes-index', [
            'applications' => $applications,
            'studentId' => $request->user()->studentNumber(),
        ]);
    }

    public function studentCreate(Request $request)
    {
        return view('spes.form', [
            'prefill' => $this->studentPrefill($request),
            'studentMode' => true,
        ]);
    }

    public function studentStore(Request $request, AuditLogger $audit)
    {
        $data = $this->fields($request, true);
        $data['reference_no'] = 'SPR-PENDING';
        $data['encoded_by'] = $request->user()->id;
        $data['user_id'] = $request->user()->id;

        $application = SpesApplication::create($data);
        $application->update([
            'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
        ]);

        $audit->log('create', 'spes', 'Student submitted SPR application '.$application->reference_no.'.');

        return redirect()->route('student.spes.show', $application)->with('success', 'Your SPR application '.$application->reference_no.' was submitted.');
    }

    public function studentShow(Request $request, SpesApplication $spes)
    {
        $this->authorizeStudent($request, $spes);

        if ($spes->studentMayEdit()) {
            return view('spes.form', [
                'application' => $spes,
                'prefill' => [],
                'studentMode' => true,
            ]);
        }

        return view('spes.show', ['application' => $spes, 'studentMode' => true]);
    }

    public function studentEdit(Request $request, SpesApplication $spes)
    {
        $this->authorizeStudent($request, $spes);
        if (! $spes->studentMayEdit()) {
            return redirect()->route('student.spes.show', $spes)->with('error', 'This application is already '.$spes->statusLabel().'. You can edit it again only if the office allows it.');
        }

        return view('spes.form', [
            'application' => $spes,
            'prefill' => [],
            'studentMode' => true,
        ]);
    }

    public function studentUpdate(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        $this->authorizeStudent($request, $spes);
        if (! $spes->studentMayEdit()) {
            return redirect()->route('student.spes.show', $spes)->with('error', 'This application can no longer be edited unless the office allows it.');
        }

        $spes->update($this->fields($request, true, $spes->status, $spes));
        $spes->update(['student_can_edit' => false]);
        $audit->log('update', 'spes', 'Student updated SPR application '.$spes->reference_no.'.');

        $message = 'Your changes were saved. The record is locked again until the office allows another edit.';

        return redirect()->route('student.spes.show', $spes)->with('success', $message);
    }

    public function studentDestroy(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        $this->authorizeStudent($request, $spes);
        if ($spes->status !== 'submitted') {
            return redirect()->route('student.spes.show', $spes)->with('error', 'Only a submitted application can be withdrawn.');
        }

        $reference = $spes->reference_no;
        $spes->delete();
        $audit->log('delete', 'spes', 'Student withdrew SPR application '.$reference.'.');

        return redirect()->route('student.spes.index')->with('success', 'Application '.$reference.' was withdrawn.');
    }

    public function studentPrint(Request $request, SpesApplication $spes)
    {
        $this->authorizeStudent($request, $spes);

        return view('spes.print', ['application' => $spes, 'studentMode' => true]);
    }

    public function studentPdf(Request $request, SpesApplication $spes, SprPdf $pdf)
    {
        $this->authorizeStudent($request, $spes);

        return $this->pdfResponse($pdf, $spes, true);
    }

    private function pdfResponse(SprPdf $pdf, SpesApplication $spes, bool $studentMode)
    {
        try {
            return $pdf->download($spes, $studentMode);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The PDF could not be created. Use Print form if the download does not start.');
        }
    }

    private function authorizeStudent(Request $request, SpesApplication $spes): void
    {
        abort_unless($spes->ownedBy($request->user()), 403);
    }

    private function studentPrefill(Request $request): array
    {
        $user = $request->user();
        $record = StudentRecord::query()->with('program')->where('student_id', $user->studentNumber())->latest('id')->first();
        $prefill = $this->prefillFromStudent((int) ($record?->id ?? 0));
        $prefill['student_id'] = $user->studentNumber();
        $prefill['last_name'] = $user->last_name;
        $prefill['first_name'] = $user->first_name;
        $prefill['middle_name'] = $user->middle_name;
        $prefill['email'] = $user->email;
        $prefill['sex'] = $prefill['sex'] ?? $user->sex;
        $prefill['contact_number'] = $user->contact_number;
        $prefill['address'] = $user->purok_street ?: $user->address;
        $prefill['barangay'] = $user->barangay;
        $prefill['city_municipality'] = $user->city_municipality;
        $prefill['province'] = $user->province;
        $prefill['zip_code'] = $user->zipcode;
        $prefill['applicant_signature'] = trim($user->first_name.' '.$user->middle_name.' '.$user->last_name);

        return array_filter($prefill, fn ($value) => $value !== null && $value !== '');
    }
}
