<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Department;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function summary(Request $request, AnalyticsService $analytics)
    {
        $data = $analytics->dashboard($request->only(['academic_year_id', 'semester_id', 'college_id', 'program_id']));
        $options = $analytics->filterOptions();

        return view('analytics.summary', array_merge($data, $options, [
            'programCount' => $data['topPrograms']->count(),
            'programRanking' => $data['topPrograms'],
            'yearLevelDist' => $data['yearLevels'],
            'typeBreakdown' => collect($data['newVsReturning'])->map(fn ($total, $type) => [
                'enrollment_type' => $type,
                'total' => $total,
            ])->values(),
            'trends' => collect($data['trends'])->map(fn ($row) => ['period' => $row['label'], 'total' => $row['total']]),
        ]));
    }

    public function trends(AnalyticsService $analytics)
    {
        $trendData = $analytics->enrollmentTrends();
        $semesterTrends = $analytics->semesterTrends();
        $options = $analytics->filterOptions();

        return view('analytics.trends', array_merge($options, [
            'trendData' => $trendData,
            'semesterTrends' => $semesterTrends,
            'comparison' => $trendData->map(fn ($row) => [
                'period' => $row['label'],
                'total' => $row['total'],
                'change' => $row['growth'],
                'growth' => $row['growth'],
            ]),
            'trendLabels' => $trendData->pluck('label'),
            'trendSeries' => [[
                'label' => 'Institution',
                'data' => $trendData->pluck('total')->values(),
            ]],
            'growthRates' => $trendData->map(fn ($row) => [
                'period' => $row['label'],
                'rate' => $row['growth'],
            ]),
        ]));
    }

    public function programAnalytics(Request $request, AnalyticsService $analytics)
    {
        $data = $analytics->dashboard($request->only(['academic_year_id', 'college_id']));
        $total = max($data['totalEnrollment'], 1);
        $programStats = $data['topPrograms']->map(function ($program) use ($total) {
            $program['growth'] = $program['growth_rate'];
            $program['share'] = round(($program['current'] / $total) * 100, 2);
            $program['college_name'] = $program['college'];

            return $program;
        });

        return view('analytics.programs', [
            'programs' => $programStats,
            'programStats' => $programStats,
            'latestAY' => $data['latestAY'],
            'heatmap' => $data['heatmap'],
            'filters' => $analytics->filterOptions(),
        ]);
    }

    public function collegeAnalytics(Request $request, AnalyticsService $analytics)
    {
        $collegeId = $request->integer('college_id') ?: null;
        $data = $analytics->dashboard($request->only(['academic_year_id', 'college_id', 'department_id']));
        $previousFilters = $data['filters'];
        if ($data['previousAY'] ?? null) {
            $previousFilters['academic_year_id'] = $data['previousAY']->id;
        }
        $previousTotals = $analytics->collegeRanking($previousFilters)->keyBy('id');
        $total = max($data['collegeRows']->sum('total'), 1);
        $collegeStats = $data['collegeRows']->map(function ($row) use ($total, $analytics, $previousTotals) {
            $row['departments'] = $row['department_count'];
            $row['programs'] = $row['program_count'];
            $row['share'] = round(($row['total'] / $total) * 100, 2);
            $row['growth'] = $analytics->growthRate($row['total'], (int) ($previousTotals[$row['id']]['total'] ?? 0));

            return $row;
        });

        $departments = $collegeId
            ? Department::query()->where('college_id', $collegeId)->with('programs')->get()
            : collect();
        $departmentStats = $analytics->departmentRanking($data['filters']);
        if ($collegeId) {
            $departmentStats = $departmentStats->whereIn('id', $departments->pluck('id'))->values();
        }

        return view('analytics.colleges', [
            'colleges' => $collegeStats,
            'collegeStats' => $collegeStats,
            'departmentStats' => $departmentStats,
            'latestAY' => $data['latestAY'],
            'selectedCollege' => $collegeId ? College::find($collegeId) : null,
            'departments' => $departments,
            'programRows' => $data['topPrograms'],
            'yearLevels' => $data['yearLevels'],
        ]);
    }

    public function retentionAnalytics(Request $request, AnalyticsService $analytics)
    {
        $filters = $request->only(['academic_year_id', 'semester_name', 'college_id', 'program_id', 'year_level']);
        $scope = collect($filters)->except('academic_year_id')->filter(fn ($value) => $value !== null && $value !== '')->all();
        $retentionData = $analytics->retentionByYear($scope);
        $latest = ! empty($filters['academic_year_id'])
            ? $retentionData->firstWhere('academic_year_id', (int) $filters['academic_year_id'])
            : null;
        $latest ??= $retentionData->reverse()->first(fn ($row) => ($row['total'] ?? 0) > 0) ?? $retentionData->last();
        $previousYearId = null;
        if ($latest) {
            $current = \App\Models\AcademicYear::query()->find($latest['academic_year_id']);
            $previousYearId = $analytics->previousAcademicYear($current)?->id;
            $filters['academic_year_id'] = $latest['academic_year_id'];
        }

        $movement = [];
        if ($latest) {
            $yearFilters = array_merge($scope, ['academic_year_id' => $latest['academic_year_id']]);
            $movement = [
                ['label' => 'New', 'count' => $analytics->typeTotal($yearFilters, 'new')],
                ['label' => 'Shiftees', 'count' => $analytics->typeTotal($yearFilters, 'shiftee')],
                ['label' => 'Transferred', 'count' => $analytics->typeTotal($yearFilters, 'transferee')],
                ['label' => 'Graduating', 'count' => $analytics->typeTotal($yearFilters, 'graduating')],
                ['label' => 'Withdrawn', 'count' => $analytics->typeTotal($yearFilters, 'withdrawn')],
                ['label' => 'Drop', 'count' => $analytics->typeTotal($yearFilters, 'dropped')],
            ];
        }

        return view('analytics.retention', array_merge($analytics->filterOptions(), [
            'filters' => $filters,
            'retentionData' => $retentionData,
            'retentionYear' => $latest['academic_year'] ?? null,
            'retentionRate' => $latest['retention_rate'] ?? 0,
            'attritionRate' => $latest['attrition_rate'] ?? 0,
            'returningStudents' => $latest['returning'] ?? 0,
            'movementCounts' => $movement,
            'programRetention' => $latest
                ? $analytics->programRetention($latest['academic_year_id'], $previousYearId, $scope)
                : collect(),
            'retentionTrend' => $retentionData->map(fn ($row) => ['period' => $row['academic_year'], 'rate' => $row['retention_rate']]),
            'attritionTrend' => $retentionData->map(fn ($row) => ['period' => $row['academic_year'], 'rate' => $row['attrition_rate']]),
        ]));
    }
}
