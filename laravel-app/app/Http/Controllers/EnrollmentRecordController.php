<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Semester;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\DataQualityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentRecordController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics)
    {
        $filters = $request->only([
            'academic_year_id', 'semester_id', 'college_id', 'program_id', 'year_level', 'enrollment_type',
        ]);

        $enrollments = $analytics->applyFilters(EnrollmentRecord::query()->with(['academicYear', 'semester', 'college', 'program']), $filters)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $options = $analytics->filterOptions();

        return view('enrollment.index', array_merge($options, compact('enrollments')));
    }

    public function create()
    {
        return view('enrollment.create', $this->formData());
    }

    public function store(Request $request, AuditLogger $audit)
    {
        EnrollmentRecord::create($this->validated($request));
        $audit->log('create', 'enrollment', 'Created enrollment record.');

        return redirect()->route('enrollment.index')->with('success', 'Enrollment record created.');
    }

    public function edit(EnrollmentRecord $enrollment)
    {
        return view('enrollment.create', array_merge($this->formData(), ['enrollment' => $enrollment]));
    }

    public function update(Request $request, EnrollmentRecord $enrollment, AuditLogger $audit)
    {
        $enrollment->update($this->validated($request, $enrollment->id));
        $audit->log('update', 'enrollment', 'Updated enrollment record #'.$enrollment->id);

        return redirect()->route('enrollment.index')->with('success', 'Enrollment record updated.');
    }

    public function destroy(EnrollmentRecord $enrollment, AuditLogger $audit)
    {
        $enrollment->delete();
        $audit->log('delete', 'enrollment', 'Deleted enrollment record #'.$enrollment->id);

        return redirect()->route('enrollment.index')->with('success', 'Enrollment record deleted.');
    }

    public function quality(DataQualityService $quality)
    {
        return view('enrollment.quality', $quality->report());
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="enrollment_records_'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['academic_year', 'semester', 'college', 'program_code', 'year_level', 'enrollment_type', 'enrollment_count']);

            EnrollmentRecord::with(['academicYear', 'semester', 'college', 'program'])
                ->orderBy('id')
                ->chunk(500, function ($records) use ($handle) {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            $record->academicYear?->academic_year,
                            $record->semester?->semester_name,
                            $record->college?->college_name,
                            $record->program?->program_code,
                            $record->year_level,
                            $record->enrollment_type,
                            $record->enrollment_count,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    private function formData(): array
    {
        return [
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'semesters' => Semester::with('academicYear')->get(),
            'colleges' => College::orderBy('college_name')->get(),
            'departments' => Department::with('college')->orderBy('department_name')->get(),
            'programs' => Program::orderBy('program_name')->get(),
        ];
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'college_id' => ['required', 'exists:colleges,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'enrollment_type' => ['required', 'string', Rule::in(['new', 'returning', 'continuing', 'transferee', 'graduating', 'withdrawn', 'dropped'])],
            'enrollment_count' => ['required', 'integer', 'min:0'],
        ]);
    }
}
