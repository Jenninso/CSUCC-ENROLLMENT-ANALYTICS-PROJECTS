@extends('layouts.app')
@section('title', 'Program Analytics')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Program Comparison</h3>
        <canvas id="programBarChart" height="250"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Program Share</h3>
        <canvas id="programPieChart" height="250"></canvas>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Program Growth Rates</h3>
            <p class="text-sm text-gray-500">Click a college below to drill into departments and programs.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Program</th>
                    <th class="px-4 py-3 font-semibold">College</th>
                    <th class="px-4 py-3 font-semibold text-right">Current</th>
                    <th class="px-4 py-3 font-semibold text-right">Previous</th>
                    <th class="px-4 py-3 font-semibold text-right">Growth</th>
                    <th class="px-4 py-3 font-semibold text-right">Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programStats ?? [] as $stat)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium text-indigo-600">{{ $stat->program_name ?? $stat['program_name'] ?? '' }}</td>
                    <td class="px-4 py-3">{{ $stat->college_name ?? $stat['college_name'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($stat->current ?? $stat['current'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($stat->previous ?? $stat['previous'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right {{ ($stat->growth ?? $stat['growth'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $stat->growth ?? $stat['growth'] ?? 0 }}%</td>
                    <td class="px-4 py-3 text-right">{{ $stat->share ?? $stat['share'] ?? 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No program data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('programBarChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($programStats ?? [])->pluck('program_name')),
            datasets: [{ label: 'Enrollment', data: @json(collect($programStats ?? [])->map(fn($s) => $s->current ?? $s['current'] ?? 0)->values()), backgroundColor: '#6366f1' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('programPieChart'), {
        type: 'pie',
        data: {
            labels: @json(collect($programStats ?? [])->pluck('program_name')),
            datasets: [{ data: @json(collect($programStats ?? [])->map(fn($s) => $s->current ?? $s['current'] ?? 0)->values()), backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9','#8b5cf6','#ec4899'] }]
        },
        options: { responsive: true }
    });
</script>
@endpush
