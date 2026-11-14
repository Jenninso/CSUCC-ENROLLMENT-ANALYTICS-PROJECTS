@extends('layouts.app')
@section('title', 'Retention Analytics')
@section('subtitle', 'Returning students and the students who did not continue.')

@section('content')
<form method="GET" class="ui-panel p-4 mb-6 grid grid-cols-2 lg:grid-cols-6 gap-3">
    <select name="academic_year_id" class="ui-input">
        <option value="">Latest year with enrollment</option>
        @foreach($academicYears ?? [] as $ay)
            <option value="{{ $ay->id }}" @selected(($filters['academic_year_id'] ?? '') == $ay->id)>{{ $ay->academic_year }}</option>
        @endforeach
    </select>
    <select name="semester_name" class="ui-input">
        <option value="">All semesters</option>
        @foreach($semesters ?? [] as $sem)
            <option value="{{ $sem->semester_name }}" @selected(($filters['semester_name'] ?? '') === $sem->semester_name)>{{ $sem->semester_name }}</option>
        @endforeach
    </select>
    <select name="college_id" class="ui-input">
        <option value="">All colleges</option>
        @foreach($colleges ?? [] as $college)
            <option value="{{ $college->id }}" @selected(($filters['college_id'] ?? '') == $college->id)>{{ $college->college_name }}</option>
        @endforeach
    </select>
    <select name="program_id" class="ui-input">
        <option value="">All programs</option>
        @foreach($programs ?? [] as $program)
            <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>{{ $program->program_code }}</option>
        @endforeach
    </select>
    <select name="year_level" class="ui-input">
        <option value="">All year levels</option>
        @for($i = 1; $i <= 4; $i++)
            <option value="{{ $i }}" @selected(($filters['year_level'] ?? '') == $i)>{{ \App\Models\EnrollmentRecord::yearLevelLabel($i) }}</option>
        @endfor
    </select>
    <button class="ui-btn-primary">Apply filters</button>
</form>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Overall Retention{{ $retentionYear ? ' · '.$retentionYear : '' }}</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $retentionRate ?? 0 }}%</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Attrition Rate{{ $retentionYear ? ' · '.$retentionYear : '' }}</p>
        <p class="text-2xl font-bold text-rose-600 mt-1">{{ $attritionRate ?? 0 }}%</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Returning / Continuing{{ $retentionYear ? ' · '.$retentionYear : '' }}</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($returningStudents ?? 0) }}</p>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @foreach($movementCounts ?? [] as $item)
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-sm text-gray-500">{{ $item['label'] }}{{ $retentionYear ? ' · '.$retentionYear : '' }}</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($item['count']) }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Retention Rate Trend</h3>
    <canvas id="retentionChart" height="100"></canvas>
</div>

<div class="bg-white rounded-lg shadow-sm p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Attrition Rate</h3>
    <canvas id="attritionChart" height="90"></canvas>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Retention by Program{{ $retentionYear ? ' · '.$retentionYear : '' }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Program</th>
                    <th class="px-4 py-3 font-semibold text-right">Cohort Size</th>
                    <th class="px-4 py-3 font-semibold text-right">Returned</th>
                    <th class="px-4 py-3 font-semibold text-right">Retention</th>
                    <th class="px-4 py-3 font-semibold text-right">Attrition</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programRetention ?? [] as $row)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3">{{ $row->program_name ?? $row['program_name'] ?? '' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->cohort ?? $row['cohort'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->returned ?? $row['returned'] ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-emerald-600 font-medium">{{ $row->retention ?? $row['retention'] ?? 0 }}%</td>
                    <td class="px-4 py-3 text-right text-rose-600">{{ $row->attrition ?? $row['attrition'] ?? 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No retention data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('retentionChart'), {
        type: 'line',
        data: {
            labels: @json(collect($retentionTrend ?? [])->pluck('period')),
            datasets: [{ label: 'Retention %', data: @json(collect($retentionTrend ?? [])->pluck('rate')), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 }]
        },
        options: { responsive: true, scales: { y: { min: 0, max: 100 } } }
    });
    new Chart(document.getElementById('attritionChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($attritionTrend ?? [])->pluck('period')),
            datasets: [{ label: 'Attrition %', data: @json(collect($attritionTrend ?? [])->pluck('rate')), backgroundColor: '#f43f5e' }]
        },
        options: { responsive: true, scales: { y: { min: 0 } } }
    });
</script>
@endpush
