@extends('layouts.app')
@section('title', 'Analytics Overview')
@section('subtitle', 'Enrollment, growth, programs, and retention for the CEIT Program.')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Total Enrollment</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalEnrollment ?? 0) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Growth Rate</p>
        <p class="text-2xl font-bold {{ ($growthRate ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">{{ $growthRate ?? 0 }}%</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Programs Offered</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $programCount ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Retention Rate</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $retentionRate ?? 0 }}%</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment Growth Trend</h3>
    <canvas id="growthChart" height="100"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Program Ranking</h3>
        <canvas id="rankingChart" height="220"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Year Level Distribution</h3>
        <canvas id="yearLevelChart" height="220"></canvas>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment Type Breakdown</h3>
    <canvas id="typeChart" height="80"></canvas>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: {
            labels: @json(collect($trends ?? [])->pluck('period')),
            datasets: [{ label: 'Enrollment', data: @json(collect($trends ?? [])->pluck('total')), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('rankingChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($programRanking ?? [])->pluck('program_name')),
            datasets: [{ label: 'Students', data: @json(collect($programRanking ?? [])->pluck('total')), backgroundColor: '#6366f1' }]
        },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('yearLevelChart'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($yearLevelDist ?? [])->pluck('year_level')),
            datasets: [{ data: @json(collect($yearLevelDist ?? [])->pluck('total')), backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e'] }]
        },
        options: { responsive: true }
    });
    new Chart(document.getElementById('typeChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($typeBreakdown ?? [])->pluck('enrollment_type')),
            datasets: [{ label: 'Count', data: @json(collect($typeBreakdown ?? [])->pluck('total')), backgroundColor: ['#10b981','#f59e0b','#0ea5e9','#8b5cf6'] }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
</script>
@endpush
