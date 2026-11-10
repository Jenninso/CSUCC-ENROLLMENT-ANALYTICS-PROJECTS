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
        $total = max($data['collegeRows']->sum('total'), 1);
        $collegeStats = $data['collegeRows']->map(function ($row) use ($total) {
            $row['departments'] = $row['department_count'];
            $row['programs'] = 0;
            $row['share'] = round(($row['total'] / $total) * 100, 2);
            $row['growth'] = 0;

            return $row;
        });

        $departments = $collegeId
            ? Department::query()->where('college_id', $collegeId)->with('programs')->get()
            : collect();

        return view('analytics.colleges', [
            'colleges' => $collegeStats,
            'collegeStats' => $collegeStats,
            'departmentStats' => $departments->map(fn ($d) => [
                'department_name' => $d->department_name,
                'total' => $data['topPrograms']->where('college', $d->college?->college_name)->sum('current'),
            ]),
            'latestAY' => $data['latestAY'],
            'selectedCollege' => $collegeId ? College::find($collegeId) : null,
            'departments' => $departments,
            'programRows' => $data['topPrograms'],
            'yearLevels' => $data['yearLevels'],
        ]);
    }

    public function retentionAnalytics(AnalyticsService $analytics)
    {
        $retentionData = $analytics->retentionByYear();
        $latest = $retentionData->last();

        return view('analytics.retention', [
            'retentionData' => $retentionData,
            'retentionRate' => $latest['retention_rate'] ?? 0,
            'attritionRate' => $latest['attrition_rate'] ?? 0,
            'returningStudents' => $latest['returning'] ?? 0,
            'programRetention' => collect(),
            'retentionTrend' => $retentionData->map(fn ($row) => ['period' => $row['academic_year'], 'rate' => $row['retention_rate']]),
            'attritionTrend' => $retentionData->map(fn ($row) => ['period' => $row['academic_year'], 'rate' => $row['attrition_rate']]),
        ]);
    }
}
