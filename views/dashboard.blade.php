@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
{{-- KPI Cards Row 1 --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Total Enrollment --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Enrollment</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalEnrollment ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            </div>
        </div>
        <div class="mt-2 flex items-center text-sm {{ ($growthRate ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
            @if(($growthRate ?? 0) >= 0)
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            @else
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            @endif
            {{ abs($growthRate ?? 0) }}% vs last period
        </div>
    </div>

    {{-- New Students --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">New Students</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($newStudents ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
            </div>
        </div>
    </div>

    {{-- Returning Students --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Returning Students</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($returningStudents ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
            </div>
        </div>
    </div>

    {{-- Retention Rate --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Retention Rate</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $retentionRate ?? 0 }}%</p>
            </div>
            <div class="w-12 h-12 bg-sky-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- KPI Cards Row 2 --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Highest Enrolled Program</p>
        <p class="text-lg font-bold text-gray-800 mt-1">{{ $highestProgram->program_name ?? 'N/A' }}</p>
        <p class="text-sm text-gray-400">{{ number_format($highestProgram->total ?? 0) }} students</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Lowest Enrolled Program</p>
        <p class="text-lg font-bold text-gray-800 mt-1">{{ $lowestProgram->program_name ?? 'N/A' }}</p>
        <p class="text-sm text-gray-400">{{ number_format($lowestProgram->total ?? 0) }} students</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Forecasted Next Period</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Active Alerts</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $activeAlertsCount ?? 0 }}</p>
    </div>
</div>

{{-- Charts --}}
<div class="mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment Trend</h3>
        <canvas id="enrollmentTrendChart" height="100"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Programs</h3>
        <canvas id="programChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">College Distribution</h3>
        <canvas id="collegeChart" height="200"></canvas>
    </div>
</div>

{{-- Recent Insights & Alerts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent AI Insights</h3>
        @forelse($insights ?? [] as $insight)
        <div class="border-b last:border-0 py-3">
            <p class="text-sm font-medium text-gray-800">{{ $insight->title ?? '' }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($insight->description ?? '', 100) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $insight->created_at?->diffForHumans() }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-400">No insights yet.</p>
        @endforelse
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Alerts</h3>
        @forelse($alerts ?? [] as $alert)
        <div class="border-b last:border-0 py-3 flex items-start gap-3">
            <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0 {{ $alert->severity === 'critical' ? 'bg-red-500' : ($alert->severity === 'warning' ? 'bg-amber-500' : 'bg-blue-500') }}"></span>
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $alert->title ?? '' }}</p>
                <p class="text-xs text-gray-400">{{ $alert->created_at?->diffForHumans() }}</p>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400">No alerts.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    const trendLabels = @json(collect($enrollmentTrends ?? [])->pluck('label'));
    const trendData = @json(collect($enrollmentTrends ?? [])->pluck('total'));
    new Chart(document.getElementById('enrollmentTrendChart'), {
        type: 'line',
        data: { labels: trendLabels, datasets: [{ label: 'Enrollment', data: trendData, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false } } }
    });

    const progLabels = @json(collect($topPrograms ?? [])->pluck('program_name'));
    const progData = @json(collect($topPrograms ?? [])->pluck('total'));
    new Chart(document.getElementById('programChart'), {
        type: 'bar',
        data: { labels: progLabels, datasets: [{ label: 'Students', data: progData, backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9','#8b5cf6','#ec4899'] }] },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
    });

    const collegeLabels = @json(collect($topPrograms ?? [])->pluck('program_name'));
    const collegeData = @json(collect($topPrograms ?? [])->pluck('total'));
    new Chart(document.getElementById('collegeChart'), {
        type: 'doughnut',
        data: { labels: collegeLabels, datasets: [{ data: collegeData, backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9','#8b5cf6'] }] },
        options: { responsive: true }
    });
</script>
@endpush
