<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Retention = returning + continuing students in the current period
     * divided by previous-period total enrollment (eligible students).
     * Attrition = 1 - retention, using the same eligible base.
     */
    public function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($filters['semester_id'] ?? null, fn ($q, $v) => $q->where('semester_id', $v))
            ->when($filters['semester_name'] ?? null, function ($q, $name) {
                $q->whereIn('semester_id', Semester::query()->where('semester_name', $name)->select('id'));
            })
            ->when($filters['college_id'] ?? null, fn ($q, $v) => $q->where('college_id', $v))
            ->when($filters['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['program_id'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->when($filters['year_level'] ?? null, fn ($q, $v) => $q->where('year_level', $v))
            ->when($filters['enrollment_type'] ?? null, fn ($q, $v) => $q->where('enrollment_type', $v));
    }

    public function latestAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()->orderByDesc('start_date')->first();
    }

    public function latestAcademicYearWithEnrollment(): ?AcademicYear
    {
        return AcademicYear::query()
            ->whereIn('id', EnrollmentRecord::query()->select('academic_year_id')->distinct())
            ->orderByDesc('start_date')
            ->first();
    }

    public function previousAcademicYear(?AcademicYear $current): ?AcademicYear
    {
        if (! $current) {
            return null;
        }

        return AcademicYear::query()
            ->where('start_date', '<', $current->start_date)
            ->orderByDesc('start_date')
            ->first();
    }

    public function total(array $filters = []): int
    {
        return (int) $this->applyFilters(EnrollmentRecord::query(), $filters)->sum('enrollment_count');
    }

    public function growthRate(int $current, int $previous): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function typeTotal(array $filters, string $type): int
    {
        return (int) $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->where('enrollment_type', $type)
            ->sum('enrollment_count');
    }

    public function retainedTotal(array $filters): int
    {
        return (int) $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->whereIn('enrollment_type', ['returning', 'continuing'])
            ->sum('enrollment_count');
    }

    public function lostTotal(array $filters): int
    {
        return (int) $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->whereIn('enrollment_type', ['withdrawn', 'dropped'])
            ->sum('enrollment_count');
    }

    public function dashboard(array $filters = []): array
    {
        $latest = $this->latestAcademicYear();
        $selected = ! empty($filters['academic_year_id'])
            ? AcademicYear::query()->find($filters['academic_year_id'])
            : ($this->latestAcademicYearWithEnrollment() ?? $latest);

        if ($selected && empty($filters['academic_year_id'])) {
            $filters['academic_year_id'] = $selected->id;
        }

        $previous = $this->previousAcademicYear($selected ?? $latest);

        $currentFilters = $filters;
        $previousFilters = $filters;
        if ($previous) {
            $previousFilters['academic_year_id'] = $previous->id;
        }

        $total = $this->total($currentFilters);
        $previousTotal = $previous ? $this->total($previousFilters) : 0;
        $growth = $this->growthRate($total, $previousTotal);
        $newStudents = $this->typeTotal($currentFilters, 'new');
        $previousNew = $previous ? $this->typeTotal($previousFilters, 'new') : 0;
        $returning = $this->typeTotal($currentFilters, 'returning') + $this->typeTotal($currentFilters, 'continuing');
        $previousReturning = $previous
            ? $this->typeTotal($previousFilters, 'returning') + $this->typeTotal($previousFilters, 'continuing')
            : 0;
        $shiftees = $this->typeTotal($currentFilters, 'shiftee');
        $previousShiftees = $previous ? $this->typeTotal($previousFilters, 'shiftee') : 0;
        $transferred = $this->typeTotal($currentFilters, 'transferee');
        $previousTransferred = $previous ? $this->typeTotal($previousFilters, 'transferee') : 0;
        $withdrawn = $this->typeTotal($currentFilters, 'withdrawn');
        $previousWithdrawn = $previous ? $this->typeTotal($previousFilters, 'withdrawn') : 0;
        $dropped = $this->typeTotal($currentFilters, 'dropped');
        $previousDropped = $previous ? $this->typeTotal($previousFilters, 'dropped') : 0;
        $retained = $this->retainedTotal($currentFilters);
        $retentionRate = $previousTotal > 0 ? round(($retained / $previousTotal) * 100, 2) : 0.0;
        $attritionRate = $previousTotal > 0 ? round((($previousTotal - $retained) / $previousTotal) * 100, 2) : 0.0;

        $programRows = $this->programRanking($currentFilters, $previousFilters);
        $collegeRows = $this->collegeRanking($currentFilters);
        $yearLevels = $this->yearLevelDistribution($currentFilters);
        $newVsReturning = [
            'new' => $newStudents,
            'returning' => $this->typeTotal($currentFilters, 'returning'),
            'continuing' => $this->typeTotal($currentFilters, 'continuing'),
            'transferee' => $this->typeTotal($currentFilters, 'transferee'),
            'graduating' => $this->typeTotal($currentFilters, 'graduating'),
            'withdrawn' => $this->typeTotal($currentFilters, 'withdrawn'),
            'dropped' => $this->typeTotal($currentFilters, 'dropped'),
        ];

        return [
            'filters' => $filters,
            'latestAY' => $latest,
            'previousAY' => $previous,
            'latestSemester' => $latest
                ? Semester::query()->where('academic_year_id', $latest->id)->orderBy('id')->first()
                : null,
            'totalEnrollment' => $total,
            'previousEnrollment' => $previousTotal,
            'growthRate' => $growth,
            'newStudents' => $newStudents,
            'newStudentsGrowth' => $this->growthRate($newStudents, $previousNew),
            'returningStudents' => $returning,
            'returningStudentsGrowth' => $this->growthRate($returning, $previousReturning),
            'shifteeStudents' => $shiftees,
            'shifteeStudentsGrowth' => $this->growthRate($shiftees, $previousShiftees),
            'shifteeRate' => $total > 0 ? round(($shiftees / $total) * 100, 2) : 0.0,
            'previousShifteeRate' => $previousTotal > 0 ? round(($previousShiftees / $previousTotal) * 100, 2) : 0.0,
            'transferredStudents' => $transferred,
            'transferredStudentsGrowth' => $this->growthRate($transferred, $previousTransferred),
            'transferredRate' => $total > 0 ? round(($transferred / $total) * 100, 2) : 0.0,
            'previousTransferredRate' => $previousTotal > 0 ? round(($previousTransferred / $previousTotal) * 100, 2) : 0.0,
            'withdrawnStudents' => $withdrawn,
            'withdrawnStudentsGrowth' => $this->growthRate($withdrawn, $previousWithdrawn),
            'withdrawnRate' => $total > 0 ? round(($withdrawn / $total) * 100, 2) : 0.0,
            'previousWithdrawnRate' => $previousTotal > 0 ? round(($previousWithdrawn / $previousTotal) * 100, 2) : 0.0,
            'droppedStudents' => $dropped,
            'droppedStudentsGrowth' => $this->growthRate($dropped, $previousDropped),
            'droppedRate' => $total > 0 ? round(($dropped / $total) * 100, 2) : 0.0,
            'previousDroppedRate' => $previousTotal > 0 ? round(($previousDropped / $previousTotal) * 100, 2) : 0.0,
            'retentionRate' => $retentionRate,
            'attritionRate' => $attritionRate,
            'highestProgram' => $programRows->first(),
            'lowestProgram' => $programRows->last(),
            'topPrograms' => $programRows->take(8)->values(),
            'decliningPrograms' => $programRows->filter(fn ($p) => ($p['growth_rate'] ?? 0) < 0)->sortBy('growth_rate')->take(5)->values(),
            'growingPrograms' => $programRows->filter(fn ($p) => ($p['growth_rate'] ?? 0) > 0)->sortByDesc('growth_rate')->take(5)->values(),
            'collegeRows' => $collegeRows,
            'yearLevels' => $yearLevels,
            'newVsReturning' => $newVsReturning,
            'trends' => $this->enrollmentTrends(),
            'heatmap' => $this->programYearHeatmap(),
            'programShare' => $programRows->map(fn ($p) => [
                'label' => $p['program_code'],
                'value' => $p['current'],
                'share' => $total > 0 ? round(($p['current'] / $total) * 100, 2) : 0,
            ]),
        ];
    }

    public function enrollmentTrends(): Collection
    {
        return EnrollmentRecord::query()
            ->select('academic_year_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('academic_year_id')
            ->with('academicYear')
            ->get()
            ->sortBy(fn ($row) => $row->academicYear?->start_date)
            ->values()
            ->map(function ($row, $index) {
                static $prev = 0;
                $total = (int) $row->total;
                $growth = $this->growthRate($total, $prev);
                $item = [
                    'academic_year_id' => $row->academic_year_id,
                    'label' => $row->academicYear?->academic_year ?? 'N/A',
                    'total' => $total,
                    'growth' => $index === 0 ? 0 : $growth,
                ];
                $prev = $total;

                return $item;
            });
    }

    public function semesterTrends(): Collection
    {
        return EnrollmentRecord::query()
            ->select('academic_year_id', 'semester_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('academic_year_id', 'semester_id')
            ->with(['academicYear', 'semester'])
            ->get()
            ->sortBy(fn ($row) => [$row->academicYear?->start_date, $row->semester_id])
            ->values()
            ->map(fn ($row) => [
                'label' => trim(($row->academicYear?->academic_year ?? '').' '.($row->semester?->semester_name ?? '')),
                'total' => (int) $row->total,
            ]);
    }

    public function programRanking(array $currentFilters, array $previousFilters = []): Collection
    {
        $current = $this->applyFilters(EnrollmentRecord::query(), $currentFilters)
            ->select('program_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('program_id')
            ->pluck('total', 'program_id');

        $previous = $this->applyFilters(EnrollmentRecord::query(), $previousFilters)
            ->select('program_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('program_id')
            ->pluck('total', 'program_id');

        return Program::query()->with('department.college')->orderBy('program_name')->get()->map(function (Program $program) use ($current, $previous) {
            $now = (int) ($current[$program->id] ?? 0);
            $then = (int) ($previous[$program->id] ?? 0);

            return [
                'id' => $program->id,
                'program_name' => $program->program_name,
                'program_code' => $program->program_code,
                'college' => $program->department?->college?->college_name,
                'current' => $now,
                'previous' => $then,
                'growth_rate' => $this->growthRate($now, $then),
                'total' => $now,
            ];
        })->sortByDesc('current')->values();
    }

    public function collegeRanking(array $filters): Collection
    {
        $totals = $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->select('college_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('college_id')
            ->pluck('total', 'college_id');

        return College::query()->withCount(['departments', 'programs'])->orderBy('college_name')->get()->map(function (College $college) use ($totals) {
            return [
                'id' => $college->id,
                'college_name' => $college->college_name,
                'college_code' => $college->college_code,
                'total' => (int) ($totals[$college->id] ?? 0),
                'department_count' => $college->departments_count,
                'program_count' => $college->programs_count,
            ];
        })->sortByDesc('total')->values();
    }

    public function departmentRanking(array $filters): Collection
    {
        $totals = $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->select('department_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return Department::query()->with('college')->orderBy('department_name')->get()->map(function (Department $department) use ($totals) {
            return [
                'id' => $department->id,
                'department_name' => $department->department_name,
                'college' => $department->college?->college_name,
                'total' => (int) ($totals[$department->id] ?? 0),
            ];
        })->sortByDesc('total')->values();
    }

    public function yearLevelDistribution(array $filters): Collection
    {
        $rows = $this->applyFilters(EnrollmentRecord::query(), $filters)
            ->select('year_level', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->get();

        return $rows->map(fn ($row) => [
            'year_level' => (int) $row->year_level,
            'label' => EnrollmentRecord::yearLevelLabel((int) $row->year_level),
            'total' => (int) $row->total,
        ]);
    }

    public function programYearHeatmap(): array
    {
        $years = AcademicYear::query()->orderBy('start_date')->get();
        $programs = Program::query()->orderBy('program_code')->get();
        $cells = EnrollmentRecord::query()
            ->select('program_id', 'academic_year_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('program_id', 'academic_year_id')
            ->get()
            ->keyBy(fn ($row) => $row->program_id.':'.$row->academic_year_id);

        $matrix = [];
        foreach ($programs as $program) {
            $row = ['program' => $program->program_code];
            foreach ($years as $year) {
                $row[$year->academic_year] = (int) ($cells[$program->id.':'.$year->id]->total ?? 0);
            }
            $matrix[] = $row;
        }

        return [
            'years' => $years->pluck('academic_year')->all(),
            'rows' => $matrix,
        ];
    }

    public function retentionByYear(array $filters = []): Collection
    {
        $years = AcademicYear::query()->orderBy('start_date')->get();
        unset($filters['academic_year_id']);

        return $years->map(function (AcademicYear $year, int $index) use ($years, $filters) {
            $current = array_merge($filters, ['academic_year_id' => $year->id]);
            $total = $this->total($current);
            $retained = $this->retainedTotal($current);
            $newStudents = $this->typeTotal($current, 'new');
            $previous = $index > 0 ? $years[$index - 1] : null;
            $previousTotal = $previous
                ? $this->total(array_merge($filters, ['academic_year_id' => $previous->id]))
                : 0;

            return [
                'academic_year_id' => $year->id,
                'academic_year' => $year->academic_year,
                'total' => $total,
                'returning' => $retained,
                'new' => $newStudents,
                'attrition' => $previousTotal > 0 ? max($previousTotal - $retained, 0) : 0,
                'retention_rate' => $previousTotal > 0 ? round(($retained / $previousTotal) * 100, 2) : 0,
                'attrition_rate' => $previousTotal > 0 ? round((max($previousTotal - $retained, 0) / $previousTotal) * 100, 2) : 0,
            ];
        });
    }

    public function programRetention(int $academicYearId, ?int $previousYearId, array $filters = []): Collection
    {
        unset($filters['academic_year_id']);
        $retained = $this->applyFilters(
            EnrollmentRecord::query()->whereIn('enrollment_type', ['returning', 'continuing']),
            array_merge($filters, ['academic_year_id' => $academicYearId])
        )
            ->select('program_id', DB::raw('SUM(enrollment_count) as total'))
            ->groupBy('program_id')
            ->pluck('total', 'program_id');

        $previous = $previousYearId
            ? $this->applyFilters(
                EnrollmentRecord::query(),
                array_merge($filters, ['academic_year_id' => $previousYearId])
            )
                ->select('program_id', DB::raw('SUM(enrollment_count) as total'))
                ->groupBy('program_id')
                ->pluck('total', 'program_id')
            : collect();

        return Program::query()->orderBy('program_name')->get()->map(function (Program $program) use ($retained, $previous) {
            $cohort = (int) ($previous[$program->id] ?? 0);
            $returned = (int) ($retained[$program->id] ?? 0);
            $retention = $cohort > 0 ? round(($returned / $cohort) * 100, 2) : 0;
            $attrition = $cohort > 0 ? round((max($cohort - $returned, 0) / $cohort) * 100, 2) : 0;

            return [
                'program_name' => $program->program_name,
                'cohort' => $cohort,
                'returned' => $returned,
                'retention' => $retention,
                'attrition' => $attrition,
            ];
        })->filter(fn ($row) => $row['cohort'] > 0 || $row['returned'] > 0)->values();
    }

    public function seriesByProgram(?int $programId = null, ?int $collegeId = null): array
    {
        $query = EnrollmentRecord::query()
            ->select('academic_year_id', DB::raw('SUM(enrollment_count) as total'))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($collegeId, fn ($q) => $q->where('college_id', $collegeId))
            ->groupBy('academic_year_id')
            ->with('academicYear')
            ->get()
            ->sortBy(fn ($row) => $row->academicYear?->start_date)
            ->values();

        return [
            'labels' => $query->map(fn ($row) => $row->academicYear?->academic_year)->all(),
            'values' => $query->map(fn ($row) => (int) $row->total)->all(),
            'ids' => $query->map(fn ($row) => $row->academic_year_id)->all(),
        ];
    }

    public function filterOptions(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'semesters' => Semester::query()
                ->whereIn('semester_name', ['First Semester', 'Second Semester'])
                ->orderBy('id')
                ->get()
                ->unique('semester_name')
                ->sortBy(fn (Semester $semester) => $semester->semester_name === 'First Semester' ? 0 : 1)
                ->values(),
            'colleges' => College::query()->orderBy('college_name')->get(),
            'programs' => Program::query()->orderBy('program_name')->get(),
        ];
    }
}
