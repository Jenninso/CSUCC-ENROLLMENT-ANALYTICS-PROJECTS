<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Alert;
use App\Models\Department;
use App\Models\Forecast;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics)
    {
        $filters = $request->only([
            'academic_year_id', 'semester_id', 'semester_name', 'college_id', 'department_id', 'program_id', 'year_level', 'enrollment_type',
        ]);

        $data = $analytics->dashboard($filters);
        $options = $analytics->filterOptions();
        $options['departments'] = Department::query()->orderBy('department_name')->get();

        $forecast = Forecast::query()->whereNull('program_id')->whereNull('college_id')->latest()->first();
        $insights = AiInsight::query()->latest()->limit(5)->get();
        $alerts = Alert::query()->latest()->limit(5)->get();
        $unreadAlerts = Alert::query()->where('is_read', false)->count();

        return view('dashboard', array_merge($data, $options, compact('forecast', 'insights', 'alerts', 'unreadAlerts', 'filters')));
    }

    public function executive(Request $request, AnalyticsService $analytics)
    {
        $data = $analytics->dashboard($request->only(['academic_year_id', 'semester_id']));
        $forecast = Forecast::query()->whereNull('program_id')->whereNull('college_id')->latest()->first();
        $insights = AiInsight::query()->latest()->limit(6)->get();
        $alerts = Alert::query()->where('is_read', false)->latest()->limit(8)->get();
        $options = $analytics->filterOptions();

        return view('executive', array_merge($data, $options, compact('forecast', 'insights', 'alerts')));
    }
}
