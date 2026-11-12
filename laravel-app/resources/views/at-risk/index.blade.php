@extends('layouts.app')
@section('title', 'At-risk students')

@section('content')
@php
    $factorLabels = [
        'low_income' => 'Low household income',
        'working_student' => 'Working student',
        'solo_parent' => 'Solo-parent household',
        'grandparent' => 'Grandparent-led household',
        'not_with_parents' => 'Not living with parents',
        'four_ps' => '4Ps beneficiary',
        'unpaid_tuition' => 'Unpaid or partial tuition',
    ];
    $yearLabels = collect($summary['by_year'])->keys()->values();
    $levels = ['low', 'moderate', 'high', 'critical'];
    $levelColors = ['#0b5d3b', '#d97706', '#ea580c', '#b91c1c'];
@endphp

<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">Student at-risk analysis</h3>
        <p class="ui-page-sub">Risk levels come from grades, attendance, failed subjects, and enrollment standing. SPR family and income details explain possible circumstances. They do not raise a student's risk level.</p>
    </div>
    @if(auth()->user()?->canAccess('enrollment.manage'))
    <form method="POST" action="{{ route('at-risk.warnings') }}">
        @csrf
        <button type="submit" class="ui-btn-primary">Check warning thresholds</button>
    </form>
    @endif
</div>

<div class="ui-panel p-4 mb-4 text-sm text-gray-700 leading-relaxed">
    Warning thresholds are GPA 2.75 or worse, attendance below 75%, two or more failed subjects, or a dropped, withdrawn, or stopped standing. A matching alert is sent once every 14 days. Access is limited to staff who can view student records.
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Students</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['total']) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($summary['enrolled']) }} currently enrolled</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Low risk</p>
        <p class="ui-kpi-value text-xl text-emerald-700">{{ number_format($summary['counts']['low']) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Moderate risk</p>
        <p class="ui-kpi-value text-xl text-amber-700">{{ number_format($summary['counts']['moderate']) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">High or critical</p>
        <p class="ui-kpi-value text-xl text-red-700">{{ number_format($summary['counts']['high'] + $summary['counts']['critical']) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($summary['counts']['critical']) }} critical</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Declining grades</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['declining']) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Frequent absences</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['frequent_absences']) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Improved after difficulty</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['improved']) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Interventions</p>
        <p class="ui-kpi-value text-xl">{{ number_format(array_sum($summary['outcomes'])) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $summary['outcomes']['improved'] }} improved · {{ $summary['outcomes']['pending'] }} pending</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Risk classification</h3>
        <canvas id="riskChart" height="180"></canvas>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Risk by year level</h3>
        <canvas id="yearChart" height="180"></canvas>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Circumstances among at-risk students</h3>
        <canvas id="factorChart" height="180"></canvas>
        <p class="text-xs text-gray-500 mt-3">Counted only for moderate, high, and critical students. These figures are context, not a reason for the classification.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Common academic indicators</h3>
        <dl class="space-y-2 text-sm">
            @foreach($summary['indicators'] as $label => $count)
            <div class="flex justify-between gap-4 border-b border-gray-100 py-1.5">
                <dt class="text-gray-600">{{ $label }}</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($count) }}</dd>
            </div>
            @endforeach
        </dl>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-3">At-risk students by section</h3>
        <dl class="space-y-2 text-sm max-h-64 overflow-y-auto">
            @forelse(collect($summary['by_section'])->filter()->sortDesc() as $section => $count)
            <div class="flex justify-between gap-4 border-b border-gray-100 py-1.5">
                <dt class="text-gray-600">{{ $section }}</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($count) }}</dd>
            </div>
            @empty
            <p class="text-sm text-gray-500">No section has a student above low risk.</p>
            @endforelse
        </dl>
    </div>
</div>

<div class="ui-panel p-4 mb-4">
    <form method="GET" action="{{ route('at-risk.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Student ID or SPR name" class="ui-input lg:col-span-2">
        <select name="level" class="ui-input">
            <option value="">All risk levels</option>
            @foreach(\App\Models\StudentRecord::RISK_LEVELS as $value => $label)
                <option value="{{ $value }}" @selected(request('level') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="year_level" class="ui-input">
            <option value="">All year levels</option>
            @foreach([1, 2, 3, 4, 5] as $year)
                <option value="{{ $year }}" @selected((string) request('year_level') === (string) $year)>{{ \App\Models\EnrollmentRecord::yearLevelLabel($year) }}</option>
            @endforeach
        </select>
        <select name="program_id" class="ui-input">
            <option value="">All courses</option>
            @foreach($programs as $id => $code)
                <option value="{{ $id }}" @selected((string) request('program_id') === (string) $id)>{{ $code }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <button type="submit" class="ui-btn-primary">Filter</button>
            <a href="{{ route('at-risk.index') }}" class="ui-btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="ui-panel overflow-x-auto">
    <table class="ui-table">
        <thead>
            <tr>
                <th class="text-left">Student</th>
                <th class="text-left">Course</th>
                <th class="text-left">GPA</th>
                <th class="text-left">Attendance</th>
                <th class="text-left">Failed</th>
                <th class="text-left">Risk</th>
                <th class="text-right">Report</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $row)
            <tr>
                <td>
                    <p class="font-mono text-xs">{{ $row['student_id'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $row['name'] ?: 'No SPR name on file' }}</p>
                </td>
                <td>{{ $row['program'] }} · {{ $row['year_label'] }} {{ $row['section'] }}</td>
                <td class="tabular-nums">{{ number_format($row['gpa'], 2) }}</td>
                <td class="tabular-nums">{{ number_format($row['attendance'], 1) }}%</td>
                <td>{{ $row['failed_subjects'] }}</td>
                <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\StudentRecord::query()->make(['risk_level' => $row['level']])->riskBadgeClass() }}">{{ $row['level_label'] }}</span></td>
                <td class="text-right"><a href="{{ route('at-risk.show', $row['student_record_id']) }}" class="ui-link text-xs">Open</a></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500 py-10">No students match this filter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    const riskColors = @json($levelColors);
    new Chart(document.getElementById('riskChart'), {
        type: 'doughnut',
        data: {
            labels: ['Low', 'Moderate', 'High', 'Critical'],
            datasets: [{ data: @json(array_values($summary['counts'])), backgroundColor: riskColors }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('yearChart'), {
        type: 'bar',
        data: {
            labels: @json($yearLabels),
            datasets: [
                @foreach($levels as $index => $level)
                { label: @json(ucfirst($level)), data: @json($yearLabels->map(fn ($year) => $summary['by_year'][$year][$level] ?? 0)->values()), backgroundColor: riskColors[{{ $index }}] },
                @endforeach
            ]
        },
        options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
    });
    new Chart(document.getElementById('factorChart'), {
        type: 'bar',
        data: {
            labels: @json(array_values($factorLabels)),
            datasets: [{ label: 'At-risk students', data: @json(array_values($summary['factors'])), backgroundColor: '#0f766e' }]
        },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>
@endpush
