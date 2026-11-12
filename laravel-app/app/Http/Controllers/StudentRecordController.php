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
            'avg_attendance' => (clone $query)->avg('attendance_percent'),
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
        return view('students.form', array_merge($this->formData(), ['student' => $student]));
    }

    public function update(Request $request, StudentRecord $student, AuditLogger $audit)
    {
        $student->update($this->validated($request, $student->id));
        $audit->log('update', 'students', 'Updated student record '.$student->student_id.'.');

        return redirect()->route('students.show', $student)->with('success', 'Student record updated. Risk level is '.$student->riskLabel().'.');
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
                'attendance_percent',
                'number_of_subjects',
                'failed_subjects',
                'units_enrolled',
                'enrolled_status',
                'student_type',
                'scholarship_status',
                'tuition_status',
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
                '95',
                '8',
                '0',
                '24',
                'Enrolled',
                'New',
                'None',
                'Paid',
            ]);
            fclose($handle);
        }, 'student_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, StudentImportService $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt,xlsx,xls', 'max:10240'],
        ], [
            'file.extensions' => 'Upload a CSV or Excel (.xlsx) file.',
        ]);

        $file = $request->file('file');
        $parsed = $importer->parse($file->getRealPath(), $file->getClientOriginalName());
        if (! $parsed['ok']) {
            return back()->withErrors(['file' => implode(' ', $parsed['errors'])]);
        }

        $report = $importer->validate($parsed['rows']);
        session(['student_import_preview' => $report]);

        return redirect()->route('students.import')->with('success', 'Validation complete. Review the report before confirming.');
    }

    public function importCommit(StudentImportService $importer, AuditLogger $audit)
    {
        $preview = session('student_import_preview');
        if (! $preview || empty($preview['valid'])) {
            return redirect()->route('students.import')->with('error', 'No validated rows to import.');
        }

        $count = $importer->commit($preview['valid']);
        $audit->log('import', 'students', "Imported {$count} student records.");
        session()->forget('student_import_preview');

        return redirect()->route('students.index')->with('success', "{$count} student records imported. Invalid rows were not stored.");
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
                'attendance_percent',
                'number_of_subjects',
                'failed_subjects',
                'units_enrolled',
                'enrolled_status',
                'student_type',
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
                            $record->attendance_percent,
                            $record->subjects_count,
                            $record->failed_subjects,
                            $record->units_enrolled,
                            $record->enrolledLabel(),
                            $record->studentTypeLabel(),
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
            'semester_id',
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
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('academic_year_id')->orderBy('id')->get(),
            'colleges' => College::query()->orderBy('college_name')->get(),
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
            'year_level' => ['required', 'integer', 'min:1', 'max:4'],
            'section' => ['required', 'string', 'max:20'],
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
            'gpa' => ['required', 'numeric', 'min:1', 'max:5'],
            'average_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'attendance_percent' => ['required', 'numeric', 'min:0', 'max:100'],
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

        return $data;
    }
}
