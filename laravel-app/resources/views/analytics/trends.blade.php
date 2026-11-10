@extends('layouts.app')
@section('title', 'Enrollment Trends')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('analytics.trends') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <select name="college_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Colleges</option>
            @foreach($colleges ?? [] as $college)
                <option value="{{ $college->id }}" {{ request('college_id') == $college->id ? 'selected' : '' }}>{{ $college->college_name }}</option>
            @endforeach
        </select>
        <select name="program_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Programs</option>
            @foreach($programs ?? [] as $program)
                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
            @endforeach
        </select>
        <select name="academic_year_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Academic Years</option>
            @foreach($academicYears ?? [] as $ay)
                <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year_name ?? $ay->academic_year }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Apply Filters</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment Over Time</h3>
    <canvas id="multiLineChart" height="110"></canvas>
</div>

<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Growth Rate</h3>
    <canvas id="growthRateChart" height="90"></canvas>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Period Comparison</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Period</th>
                    <th class="px-4 py-3 font-semibold text-right">Enrollment</th>
                    <th class="px-4 py-3 font-semibold text-right">Change</th>
                    <th class="px-4 py-3 font-semibold text-right">Growth Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comparison ?? [] as $row)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3">{{ $row['period'] ?? '' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row['total'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right {{ ($row['change'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $row['change'] ?? 0 }}</td>
                    <td class="px-4 py-3 text-right {{ ($row['growth'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $row['growth'] ?? 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No trend data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const colors = ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9','#8b5cf6'];
    const series = @json($trendSeries ?? []);
    new Chart(document.getElementById('multiLineChart'), {
        type: 'line',
        data: {
            labels: @json($trendLabels ?? []),
            datasets: (series.length ? series : [{ label: 'Enrollment', data: [], borderColor: colors[0] }]).map((s, i) => ({
                label: s.label, data: s.data, borderColor: colors[i % colors.length], backgroundColor: 'transparent', tension: 0.3
            }))
        },
        options: { responsive: true }
    });
    new Chart(document.getElementById('growthRateChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($growthRates ?? [])->pluck('period')),
            datasets: [{ label: 'Growth %', data: @json(collect($growthRates ?? [])->pluck('rate')), backgroundColor: '#10b981' }]
        },
        options: { responsive: true }
    });
</script>
@endpush
