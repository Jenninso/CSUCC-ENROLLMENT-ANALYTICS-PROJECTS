<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiInsight;
use App\Models\Alert;
use App\Models\Forecast;
use App\Services\AnalyticsService;
use App\Services\ForecastService;
use App\Services\InsightService;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    public function enrollment(Request $request, AnalyticsService $analytics)
    {
        return response()->json($analytics->dashboard($request->all()));
    }

    public function trends(AnalyticsService $analytics)
    {
        return response()->json($analytics->enrollmentTrends());
    }

    public function programs(Request $request, AnalyticsService $analytics)
    {
        $data = $analytics->dashboard($request->all());

        return response()->json($data['topPrograms']);
    }

    public function colleges(Request $request, AnalyticsService $analytics)
    {
        return response()->json($analytics->dashboard($request->all())['collegeRows']);
    }

    public function retention(AnalyticsService $analytics)
    {
        return response()->json($analytics->retentionByYear());
    }

    public function summary(Request $request, AnalyticsService $analytics)
    {
        $data = $analytics->dashboard($request->all());

        return response()->json([
            'total' => $data['totalEnrollment'],
            'growth_rate' => $data['growthRate'],
            'retention_rate' => $data['retentionRate'],
            'new_students' => $data['newStudents'],
            'returning_students' => $data['returningStudents'],
        ]);
    }

    public function forecast(Request $request, ForecastService $forecasts)
    {
        if ($request->isMethod('post')) {
            return response()->json($forecasts->generate(
                $request->integer('program_id') ?: null,
                $request->integer('college_id') ?: null,
                $request->integer('horizon', 1),
            ));
        }

        return response()->json(Forecast::latest()->limit(20)->get());
    }

    public function insights(Request $request, InsightService $insights)
    {
        if ($request->isMethod('post')) {
            return response()->json($insights->generate());
        }

        return response()->json(AiInsight::latest()->limit(20)->get());
    }

    public function alerts()
    {
        return response()->json(Alert::latest()->limit(50)->get());
    }
}
