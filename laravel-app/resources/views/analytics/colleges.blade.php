@extends('layouts.app')
@section('title', 'College Analytics')
@section('subtitle', 'Compare colleges and the departments under them.')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">College Comparison</h3>
    <canvas id="collegeBarChart" height="120"></canvas>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">College Enrollment</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">College</th>
                    <th class="px-4 py-3 font-semibold text-right">Departments</th>
                    <th class="px-4 py-3 font-semibold text-right">Programs</th>
                    <th class="px-4 py-3 font-semibold text-right">Enrollment</th>
                    <th class="px-4 py-3 font-semibold text-right">Share</th>
                    <th class="px-4 py-3 font-semibold text-right">Growth</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collegeStats ?? [] as $stat)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium">
                        <a class="text-indigo-600" href="{{ route('analytics.colleges', ['college_id' => $stat['id'] ?? $stat->id ?? null]) }}">{{ $stat->college_name ?? $stat['college_name'] ?? '' }}</a>
                    </td>
                    <td class="px-4 py-3 text-right">{{ $stat->departments ?? $stat['departments'] ?? 0 }}</td>
                    <td class="px-4 py-3 text-right">{{ $stat->programs ?? $stat['programs'] ?? 0 }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($stat->total ?? $stat['total'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ $stat->share ?? $stat['share'] ?? 0 }}%</td>
                    <td class="px-4 py-3 text-right {{ ($stat->growth ?? $stat['growth'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $stat->growth ?? $stat['growth'] ?? 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No college data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Department Breakdown</h3>
    <canvas id="deptChart" height="120"></canvas>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('collegeBarChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($collegeStats ?? [])->pluck('college_name')),
            datasets: [{ label: 'Enrollment', data: @json(collect($collegeStats ?? [])->map(fn($s) => $s->total ?? $s['total'] ?? 0)->values()), backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9'] }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($departmentStats ?? [])->pluck('department_name')),
            datasets: [{ label: 'Enrollment', data: @json(collect($departmentStats ?? [])->pluck('total')), backgroundColor: '#0ea5e9' }]
        },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
    });
</script>
@endpush
