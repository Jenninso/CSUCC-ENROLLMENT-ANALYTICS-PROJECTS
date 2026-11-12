<?php

namespace App\Http\Controllers;

use App\Models\SpesApplication;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function create(Request $request)
    {
        return view('spes.form', [
            'prefill' => $this->prefillFromStudent($request->integer('student')),
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $this->fields($request);
        $data['reference_no'] = 'SPES-PENDING';
        $data['encoded_by'] = $request->user()?->id;

        $application = SpesApplication::create($data);
        $application->update([
            'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
        ]);

        $audit->log('create', 'spes', 'Encoded SPES application '.$application->reference_no.' for '.$application->fullName().'.');

        return redirect()->route('spes.show', $application)->with('success', 'SPES application '.$application->reference_no.' saved.');
    }

    public function show(SpesApplication $spes)
    {
        $spes->load(['studentRecord.program', 'encoder']);

        return view('spes.show', ['application' => $spes]);
    }

    public function edit(SpesApplication $spes)
    {
        return view('spes.form', [
            'application' => $spes,
            'prefill' => [],
        ]);
    }

    public function update(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        $data = $this->fields($request);
        $data['student_can_edit'] = in_array($data['status'], ['approved', 'employed', 'completed'], true)
            && $request->boolean('allow_student_edit');
        $spes->update($data);
        $audit->log('update', 'spes', 'Updated SPES application '.$spes->reference_no.'.');

        return redirect()->route('spes.show', $spes)->with('success', 'SPES application updated.');
    }

    public function destroy(SpesApplication $spes, AuditLogger $audit)
    {
        $reference = $spes->reference_no;
        $spes->delete();
        $audit->log('delete', 'spes', 'Deleted SPES application '.$reference.'.');

        return redirect()->route('spes.index')->with('success', 'SPES application deleted.');
    }

    public function allowStudentEdit(Request $request, SpesApplication $spes, AuditLogger $audit)
    {
        if (! $spes->isLocked()) {
            return back()->with('error', 'The student can still edit this application until it is approved.');
        }

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

    public function fields(Request $request, bool $asStudent = false, ?string $keepStatus = null): array
    {
        $currentYear = (int) now()->year;
        $data = $request->validate([
            'student_record_id' => ['nullable', 'integer', 'exists:student_records,id'],
            'student_id' => ['nullable', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'name_extension' => ['nullable', 'string', 'max:20'],
            'sex' => ['required', Rule::in(array_keys(SpesApplication::SEXES))],
            'civil_status' => ['required', Rule::in(array_keys(SpesApplication::CIVIL_STATUSES))],
            'birth_date' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:100'],
            'city_municipality' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'parents_annual_income' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'number_of_siblings' => ['required', 'integer', 'min:0', 'max:30'],
            'education_level' => ['required', Rule::in(array_keys(SpesApplication::EDUCATION_LEVELS))],
            'school_name' => ['required', 'string', 'max:180'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', Rule::in(SpesApplication::YEAR_LEVELS)],
            'general_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'enrollment_status' => ['required', Rule::in(array_keys(SpesApplication::ENROLLMENT_STATUSES))],
            'application_year' => ['required', 'integer', 'min:1992', 'max:'.($currentYear + 1)],
            'work_period' => ['required', Rule::in(array_keys(SpesApplication::WORK_PERIODS))],
            'preferred_days' => ['required', 'integer', 'min:20', 'max:78'],
            'beneficiary_type' => ['required', Rule::in(array_keys(SpesApplication::BENEFICIARY_TYPES))],
            'previous_spes' => ['required', 'boolean'],
            'previous_year' => ['nullable', 'integer', 'min:1992', 'max:'.$currentYear, 'required_if:previous_spes,1'],
            'preferred_assignment' => ['nullable', 'string', 'max:150'],
            'skills' => ['nullable', 'string', 'max:255'],
            'has_birth_certificate' => ['required', 'boolean'],
            'has_enrollment_cert' => ['required', 'boolean'],
            'has_grades' => ['required', 'boolean'],
            'has_indigency' => ['required', 'boolean'],
            'has_photo' => ['required', 'boolean'],
            'application_date' => ['required', 'date', 'before_or_equal:today'],
            'applicant_signature' => ['required', 'string', 'max:150'],
            'certified' => ['accepted'],
            'status' => ['required', Rule::in(array_keys(SpesApplication::STATUSES))],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $age = $request->date('birth_date')->age;
        if ($age < 15 || $age > 30) {
            throw ValidationException::withMessages([
                'birth_date' => 'SPES applicants must be at least 15 and not more than 30 years old.',
            ]);
        }

        unset($data['certified']);
        $data['age'] = $age;
        $data['student_record_id'] = ($data['student_record_id'] ?? null) ?: null;
        $data['student_id'] = trim((string) ($data['student_id'] ?? '')) ?: null;
        if ($asStudent) {
            $studentId = $request->user()->studentNumber();
            $data['student_id'] = $studentId;
            $data['status'] = $keepStatus ?: 'submitted';
            $data['student_record_id'] = StudentRecord::query()
                ->where('student_id', $studentId)
                ->latest('id')
                ->value('id');
            unset($data['remarks']);
        }
        $data['previous_spes'] = $request->boolean('previous_spes');
        $data['previous_year'] = $data['previous_spes'] ? (($data['previous_year'] ?? null) ?: null) : null;

        foreach (array_keys(SpesApplication::DOCUMENTS) as $field) {
            $data[$field] = $request->boolean($field);
        }

        return $data;
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
        $data['reference_no'] = 'SPES-PENDING';
        $data['encoded_by'] = $request->user()->id;
        $data['user_id'] = $request->user()->id;

        $application = SpesApplication::create($data);
        $application->update([
            'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
        ]);

        $audit->log('create', 'spes', 'Student submitted SPES application '.$application->reference_no.'.');

        return redirect()->route('student.spes.show', $application)->with('success', 'Your SPES application '.$application->reference_no.' was submitted.');
    }

    public function studentShow(Request $request, SpesApplication $spes)
    {
        $this->authorizeStudent($request, $spes);

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

        $wasLocked = $spes->isLocked();
        $spes->update($this->fields($request, true, $spes->status));
        if ($wasLocked) {
            $spes->update(['student_can_edit' => false]);
        }
        $audit->log('update', 'spes', 'Student updated SPES application '.$spes->reference_no.'.');

        $message = $wasLocked
            ? 'Your changes were saved. The application is locked again until the office allows another edit.'
            : 'Your SPES application was updated.';

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
        $audit->log('delete', 'spes', 'Student withdrew SPES application '.$reference.'.');

        return redirect()->route('student.spes.index')->with('success', 'Application '.$reference.' was withdrawn.');
    }

    public function studentPrint(Request $request, SpesApplication $spes)
    {
        $this->authorizeStudent($request, $spes);

        return view('spes.print', ['application' => $spes, 'studentMode' => true]);
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
