<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Forecast;
use App\Models\Program;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\ForecastService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('forecasting.index', [
            'result' => ['ok' => false, 'models' => [], 'message' => 'Choose a scope and run a forecast. Historical series will appear after the first run.'],
            'history' => Forecast::query()->with(['program', 'college'])->latest()->limit(20)->get(),
            'programs' => Program::orderBy('program_name')->get(),
            'colleges' => College::orderBy('college_name')->get(),
            'trends' => $analytics->enrollmentTrends(),
        ]);
    }

    public function store(Request $request, ForecastService $forecasts, AuditLogger $audit)
    {
        $validated = $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'college_id' => ['nullable', 'exists:colleges,id'],
            'horizon' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $result = $forecasts->generate(
            $validated['program_id'] ?? null,
            $validated['college_id'] ?? null,
            (int) $validated['horizon'],
        );

        $audit->log('forecast', 'forecasting', 'Generated '.($result['scope'] ?? 'unavailable').' forecast.');

        return view('forecasting.index', [
            'result' => $result,
            'history' => Forecast::with(['program', 'college'])->latest()->limit(20)->get(),
            'programs' => Program::orderBy('program_name')->get(),
            'colleges' => College::orderBy('college_name')->get(),
            'trends' => collect(),
        ]);
    }
}
