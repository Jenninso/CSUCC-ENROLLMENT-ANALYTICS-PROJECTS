@extends('layouts.app')
@section('title', 'Analytics Overview')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5"><p class="text-sm text-gray-500">Total Enrollment</p><p class="text-2xl font-bold">{{ number_format($totalEnrollment) }}</p></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><p class="text-sm text-gray-500">New Students</p><p class="text-2xl font-bold">{{ number_format($newStudents) }}</p></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><p class="text-sm text-gray-500">Returning</p><p class="text-2xl font-bold">{{ number_format($returningStudents) }}</p></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><p class="text-sm text-gray-500">Retention Rate</p><p class="text-2xl font-bold">{{ $retentionRate }}%</p></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Enrollment Trend</h3><canvas id="trendChart"></canvas></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Program Ranking</h3><canvas id="programChart"></canvas></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Year Level Distribution</h3><canvas id="yearChart"></canvas></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Enrollment Type Breakdown</h3><canvas id="typeChart"></canvas></div>
</div>
@endsection
@push('scripts')
<script>
new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: @json($trends->map(fn($t) => $t->academic_year.' '.$t->semester_name)), datasets: [{ data: @json($trends->pluck('total')), borderColor: '#6366f1', fill: false }] } });
new Chart(document.getElementById('programChart'), { type: 'bar', data: { labels: @json($programRanking->take(10)->pluck('program_name')), datasets: [{ data: @json($programRanking->take(10)->pluck('total')), backgroundColor: '#6366f1' }] }, options: { indexAxis: 'y' } });
new Chart(document.getElementById('yearChart'), { type: 'bar', data: { labels: @json($yearLevelDistribution->pluck('year_level')), datasets: [{ data: @json($yearLevelDistribution->pluck('total')), backgroundColor: '#10b981' }] } });
new Chart(document.getElementById('typeChart'), { type: 'doughnut', data: { labels: @json($typeBreakdown->pluck('enrollment_type')), datasets: [{ data: @json($typeBreakdown->pluck('total')), backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9'] }] } });
</script>
@endpush
