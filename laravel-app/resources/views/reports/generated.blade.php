@extends('layouts.app')
@section('title', 'Management report')

@section('content')
<div class="max-w-4xl bg-white rounded-lg shadow-sm p-8 print:shadow-none">
    <div class="flex justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Enrollment management report</h1>
            <p class="text-sm text-gray-500">Generated {{ $generated_at }} · Print this page to save as PDF</p>
        </div>
        <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg print:hidden">Print / PDF</button>
    </div>
    <h2 class="font-semibold mt-4">Executive summary</h2>
    <p class="text-sm mt-2">Total enrollment is {{ number_format($data['totalEnrollment']) }} with year-over-year growth of {{ $data['growthRate'] }}% and retention of {{ $data['retentionRate'] }}%. Retention is defined as returning and continuing students divided by previous-year total enrollment.</p>
    <h2 class="font-semibold mt-4">Program analysis</h2>
    <ul class="text-sm list-disc ml-5">
        @foreach($data['topPrograms']->take(8) as $program)
            <li>{{ $program['program_name'] }}: {{ number_format($program['current']) }} ({{ $program['growth_rate'] }}%)</li>
        @endforeach
    </ul>
    <h2 class="font-semibold mt-4">Forecast</h2>
    <p class="text-sm">{{ $forecast ? $forecast->forecast_period.' estimate '.$forecast->predicted_enrollment.' using '.$forecast->model_used : 'No forecast has been generated yet.' }}</p>
    <h2 class="font-semibold mt-4">AI insights</h2>
    <ul class="text-sm list-disc ml-5">
        @foreach($insights as $insight)
            <li>{{ $insight->title }} — {{ $insight->recommendation }}</li>
        @endforeach
    </ul>
    <h2 class="font-semibold mt-4">Methodology</h2>
    <p class="text-sm">Descriptive totals are sums of aggregated enrollment records. Growth uses (current − previous) / previous × 100. Forecasts compare naive, moving-average, linear, and exponential-smoothing models and keep the lowest MAE. AI text is generated from these metrics and is advisory only.</p>
    <h2 class="font-semibold mt-4">Data quality</h2>
    <p class="text-sm">Score {{ $quality['score'] }}% with {{ $quality['issue_count'] }} flagged issues across {{ $quality['total_records'] }} records.</p>
</div>
@endsection
