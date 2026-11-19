<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Program;
use App\Models\Semester;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use App\Services\StudentImportService;
use App\Services\StudentRiskAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $summary = [
            'total' => (clone $query)->count(),
            'low' => (clone $query)->where('risk_level', 'low')->count(),
            'moderate' => (clone $query)->where('risk_level', 'moderate')->count(),
            'high' => (clone $query)->where('risk_level', 'high')->count(),
            'critical' => (clone $query)->where('risk_level', 'critical')->count(),
            'avg_gpa' => (clone $query)->avg('gpa'),
        ];

        $students = $query
            ->orderBy('student_id')
            ->orderByDesc('academic_year_id')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', array_merge($this->formData(), [
            'students' => $students,
            'summary' => $summary,
            'sections' => StudentRecord::query()->select('section')->distinct()->orderBy('section')->pluck('section'),
        ]));
    }

    public function create()
    {
        return view('students.form', $this->formData());
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $record = StudentRecord::create($this->validated($request));
        $audit->log('create', 'students', 'Created student record '.$record->student_id.'.');

        return redirect()->route('students.index')->with('success', 'Student record saved. Risk level is '.$record->riskLabel().'.');
    }

    public function show(StudentRecord $student, StudentRiskAnalysis $analysis)
    {
        $student->load(['program.department.college', 'academicYear', 'semester']);

        return view('students.show', [
            'student' => $student,
            'analysis' => $analysis->forStudent($student),
        ]);
    }

    public function edit(StudentRecord $student)
    {
        abort(403, 'Student records are read-only. Re-import the dataset to change values.');
    }

    public function update(Request $request, StudentRecord $student, AuditLogger $audit)
    {
        abort(403, 'Student records are read-only. Re-import the dataset to change values.');
    }

    public function destroy(StudentRecord $student, AuditLogger $audit)
    {
        $studentId = $student->student_id;
        $student->delete();
        $audit->log('delete', 'students', 'Deleted student record '.$studentId.'.');

        return redirect()->route('students.index')->with('success', 'Student record deleted.');
    }

    public function importForm()
    {
        return view('students.import', [
            'preview' => session('student_import_preview'),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'student_id',
                'gender',
                'age',
                'college',
                'course',
                'year_level',
                'section',
                'academic_year',
                'semester',
                'gpa',
                'average_grade',
                'number_of_subjects',
                'failed_subjects',
                'units_enrolled',
                'enrolled_status',
                'student_type',
                'scholarship_status',
                'tuition_status',
                'last_name',
                'first_name',
                'middle_name',
                'ext_name',
                'email',
                'date_of_birth',
                'mobile',
                'academic_standing',
                'block',
                'scholar_code',
                'home_address',
                'municipality',
                'province',
            ]);
            fputcsv($handle, [
                '2025-01001',
                'Female',
                '19',
                'CEIT',
                'BSIT',
                '1',
                'A',
                '2025-2026',
                'First Semester',
                '1.75',
                '90',
                '8',
                '0',
                '24',
                'Enrolled',
                'New',
                'None',
                'Paid',
                'Dela Cruz',
                'Ana',
                'Lopez',
                '',
                'ana@example.com',
                '2006-05-01',
                '09171234567',
                'Regular',
                'BSIT1A',
                '',
                'Cabadbaran City',
                'Cabadbaran City',
                'Agusan del Norte',
            ]);
            fclose($handle);
        }, 'student_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, StudentImportService $importer)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $request->validate([
            // 50 MB — large CEIT extracts; preview payload is file-backed (not session).
            'file' => ['required', 'file', 'extensions:csv,txt,xlsx,xls', 'max:51200'],
        ], [
            'file.extensions' => 'Upload a CSV or Excel (.xlsx) file.',
            'file.max' => 'The file may be up to 50 MB.',
        ]);

        $file = $request->file('file');
        $parsed = $importer->parse($file->getRealPath(), $file->getClientOriginalName());
        if (! $parsed['ok']) {
            return back()->withErrors(['file' => implode(' ', $parsed['errors'])]);
        }

        $report = $importer->validate($parsed['rows']);
        $token = (string) Str::uuid();
        Storage::disk('local')->put(
            'imports/'.$token.'.json',
            json_encode($report['valid'] ?? [], JSON_THROW_ON_ERROR)
        );

        // Keep only a light summary in session so large Excel uploads stay scalable.
        $issues = array_slice($report['issues'] ?? [], 0, 200);
        session([
            'student_import_preview' => [
                'token' => $token,
                'score' => $report['score'] ?? 0,
                'counts' => $report['counts'] ?? ['rows' => 0, 'valid' => 0, 'invalid' => 0],
                'issues' => $issues,
                'issues_truncated' => count($report['issues'] ?? []) > 200,
                'header' => $parsed['header'] ?? [],
                'file_name' => $file->getClientOriginalName(),
            ],
        ]);

        return redirect()->route('students.import')->with('success', 'Validation complete. Review the report before confirming.');
    }

    public function importCommit(StudentImportService $importer, AuditLogger $audit)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $preview = session('student_import_preview');
        $token = is_array($preview) ? ($preview['token'] ?? null) : null;
        $path = $token ? 'imports/'.$token.'.json' : null;
        $valid = [];
        if ($path && Storage::disk('local')->exists($path)) {
            $valid = json_decode(Storage::disk('local')->get($path), true) ?: [];
        } elseif (is_array($preview) && ! empty($preview['valid'])) {
            // Backward-compatible with older in-session previews.
            $valid = $preview['valid'];
        }

        if ($valid === []) {
            return redirect()->route('students.import')->with('error', 'No validated rows to import.');
        }

        $result = $importer->commit($valid);
        $imported = (int) ($result['imported'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);
        $audit->log('import', 'students', "Imported {$imported} student records ({$skipped} skipped as duplicates).");
        session()->forget('student_import_preview');
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        if ($imported === 0 && $skipped > 0) {
            return redirect()->route('students.index')->with(
                'success',
                "No new rows imported. {$skipped} records were already in the system for the same student, academic year, and semester."
            );
        }

        $message = "{$imported} student records imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} duplicate rows were skipped.";
        }

        return redirect()->route('students.index')->with('success', $message);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'student_records_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'student_id',
                'gender',
                'age',
                'college',
                'student_group',
                'course',
                'year_level',
                'section',
                'academic_year',
                'semester',
                'gpa',
                'average_grade',
                'number_of_subjects',
                'failed_subjects',
                'units_enrolled',
                'enrolled_status',
                'student_type',
                'academic_standing',
                'scholarship_status',
                'tuition_status',
                'student_risk_level',
            ]);

            $this->filteredQuery($request)
                ->orderBy('student_id')
                ->chunk(200, function ($records) use ($handle) {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            $record->student_id,
                            $record->genderLabel(),
                            $record->age,
                            $record->program?->department?->college?->college_code,
                            $record->academicGroup(),
                            $record->program?->program_code,
                            $record->year_level,
                            $record->section,
                            $record->academicYear?->academic_year,
                            $record->semester?->semester_name,
                            $record->gpa,
                            $record->average_grade,
                            $record->subjects_count,
                            $record->failed_subjects,
                            $record->units_enrolled,
                            $record->enrolledLabel(),
                            $record->studentTypeLabel(),
                            $record->academicStandingLabel(),
                            $record->scholarshipLabel(),
                            $record->tuitionLabel(),
                            $record->riskLabel(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request)
    {
        $query = StudentRecord::query()->with(['program.department.college', 'academicYear', 'semester']);

        if ($request->filled('q')) {
            $query->where('student_id', 'like', '%'.strtoupper(trim((string) $request->input('q'))).'%');
        }

        foreach ([
            'academic_year_id',
            'program_id',
            'year_level',
            'section',
            'gender',
            'enrolled_status',
            'student_type',
            'scholarship_status',
            'tuition_status',
            'risk_level',
        ] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        // Filter by term name (First / Second / Summer), not a specific semester row.
        if ($request->filled('semester')) {
            $semesterName = (string) $request->input('semester');
            $query->whereHas('semester', fn ($semester) => $semester->where('semester_name', $semesterName));
        } elseif ($request->filled('semester_id')) {
            $query->where('semester_id', $request->input('semester_id'));
        }

        if ($request->filled('college_id')) {
            $collegeId = $request->input('college_id');
            $query->whereHas('program.department', function ($department) use ($collegeId) {
                $department->where('college_id', $collegeId);
            });
        }

        return $query;
    }

    private function formData(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderByDesc('academic_year')->get(),
            'semesters' => Semester::query()
                ->with('academicYear')
                ->join('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
                ->orderByDesc('academic_years.academic_year')
                ->orderBy('semesters.semester_order')
                ->orderBy('semesters.id')
                ->select('semesters.*')
                ->get(),
            'semesterNames' => Semester::query()
                ->select('semester_name')
                ->selectRaw('MIN(semester_order) as semester_order')
                ->groupBy('semester_name')
                ->orderBy('semester_order')
                ->pluck('semester_name'),
            'colleges' => College::forFilters(),
            'programs' => Program::query()->with('department.college')->orderBy('program_name')->get(),
        ];
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('student_records', 'student_id')
                    ->where(fn ($query) => $query
                        ->where('academic_year_id', $request->input('academic_year_id'))
                        ->where('semester_id', $request->input('semester_id')))
                    ->ignore($ignoreId),
            ],
            'gender' => ['required', Rule::in(array_keys(StudentRecord::GENDERS))],
            'age' => ['required', 'integer', 'min:15', 'max:80'],
            'college_id' => ['required', 'exists:colleges,id'],
            'program_id' => [
                'required',
                'exists:programs,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $program = Program::query()->with('department')->find($value);
                    if ($program && (int) $program->department?->college_id !== (int) $request->input('college_id')) {
                        $fail('The course must belong to the selected college.');
                    }
                },
            ],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'section' => ['required', 'string', 'max:20'],
            'block' => ['nullable', 'string', 'max:40'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => [
                'required',
                'exists:semesters,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $semester = Semester::query()->find($value);
                    if ($semester && (int) $semester->academic_year_id !== (int) $request->input('academic_year_id')) {
                        $fail('The semester must belong to the selected academic year.');
                    }
                },
            ],
            'last_name' => ['nullable', 'string', 'max:100'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'ext_name' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'date_of_birth' => ['nullable', 'date'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'academic_standing' => ['nullable', Rule::in(array_keys(StudentRecord::ACADEMIC_STANDINGS))],
            'scholar_code' => ['nullable', 'string', 'max:40'],
            'home_address' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'gpa' => ['required', 'numeric', 'min:1', 'max:5'],
            'average_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'subjects_count' => ['required', 'integer', 'min:0', 'max:20'],
            'failed_subjects' => ['required', 'integer', 'min:0', 'lte:subjects_count'],
            'units_enrolled' => ['required', 'numeric', 'min:0', 'max:36'],
            'enrolled_status' => ['required', Rule::in(array_keys(StudentRecord::ENROLLED_STATUSES))],
            'student_type' => ['required', Rule::in(array_keys(StudentRecord::STUDENT_TYPES))],
            'scholarship_status' => ['required', Rule::in(array_keys(StudentRecord::SCHOLARSHIP_STATUSES))],
            'tuition_status' => ['required', Rule::in(array_keys(StudentRecord::TUITION_STATUSES))],
        ], [
            'student_id.regex' => 'Student ID may contain only letters, numbers, and dashes.',
            'student_id.unique' => 'This Student ID already has a record for the selected academic year and semester.',
            'failed_subjects.lte' => 'Failed subjects cannot be more than the number of subjects.',
        ]);

        unset($data['risk_level'], $data['college_id']);
        $data['attendance_percent'] = 100;

        return $data;
    }
}
