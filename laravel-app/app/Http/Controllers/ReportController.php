<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Alert;
use App\Models\Forecast;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\DataQualityService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('reports.index', $analytics->filterOptions());
    }

    public function store(Request $request, AnalyticsService $analytics, DataQualityService $quality, AuditLogger $audit)
    {
        $validated = $request->validate([
            'report_type' => ['required', 'in:executive,enrollment_summary,program_analysis,college_analysis,retention'],
            'format' => ['required', 'in:csv,pdf,xlsx'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'college_id' => ['nullable', 'exists:colleges,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
        ]);

        $data = $analytics->dashboard($validated);
        $payload = [
            'data' => $data,
            'quality' => $quality->report(),
            'insights' => AiInsight::latest()->limit(8)->get(),
            'forecast' => Forecast::latest()->first(),
            'alerts' => Alert::latest()->limit(8)->get(),
            'filters' => $validated,
            'generated_at' => now(),
        ];

        $audit->log('generate_report', 'reports', $validated['report_type'].' as '.$validated['format']);

        if (in_array($validated['format'], ['csv', 'xlsx'], true)) {
            return $this->csv($payload, $validated['report_type']);
        }

        return view('reports.generated', $payload);
    }

    private function csv(array $payload, string $type): StreamedResponse
    {
        $filename = $type.'_'.now()->format('Y-m-d_His').'.csv';

        return response()->stream(function () use ($payload) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Metric', 'Value']);
            fputcsv($handle, ['Overview', 'Total enrollment', $payload['data']['totalEnrollment']]);
            fputcsv($handle, ['Overview', 'Growth rate %', $payload['data']['growthRate']]);
            fputcsv($handle, ['Overview', 'Retention rate %', $payload['data']['retentionRate']]);
            fputcsv($handle, ['Overview', 'Data quality score %', $payload['quality']['score']]);
            foreach ($payload['data']['topPrograms'] as $program) {
                fputcsv($handle, ['Program', $program['program_code'], $program['current']]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
