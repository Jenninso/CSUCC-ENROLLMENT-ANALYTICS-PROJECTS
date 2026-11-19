@extends('layouts.app')
@section('title', 'At-risk students')
@section('hidePageHead', '1')

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
    $levelTones = [
        'low' => ['label' => 'Low risk', 'border' => '#0b5d3b', 'text' => 'text-emerald-700'],
        'moderate' => ['label' => 'Moderate risk', 'border' => '#d97706', 'text' => 'text-amber-700'],
        'high' => ['label' => 'High risk', 'border' => '#ea580c', 'text' => 'text-orange-700'],
        'critical' => ['label' => 'Critical risk', 'border' => '#b91c1c', 'text' => 'text-red-700'],
    ];
    $share = function (int $count) use ($summary): string {
        $total = (int) ($summary['total'] ?? 0);

        return $total > 0 ? number_format($count / $total * 100, 1).'% of the file' : 'No students in the file';
    };
    $indicatorMax = max(1, (int) max($summary['indicators'] ?: [0]));
    $sectionMax = max(1, (int) collect($summary['by_section'])->max());
    $indicatorTones = [
        'Declining grades' => ['bar' => '#d97706', 'text' => 'text-amber-700', 'track' => 'rgba(217, 119, 6, 0.16)'],
        'Frequent absences' => ['bar' => '#ca8a04', 'text' => 'text-yellow-700', 'track' => 'rgba(202, 138, 4, 0.16)'],
        'Failed subjects' => ['bar' => '#ea580c', 'text' => 'text-orange-700', 'track' => 'rgba(234, 88, 12, 0.16)'],
        'Poor GPA' => ['bar' => '#dc2626', 'text' => 'text-red-700', 'track' => 'rgba(220, 38, 38, 0.16)'],
        'Left or stopped' => ['bar' => '#b91c1c', 'text' => 'text-red-800', 'track' => 'rgba(185, 28, 28, 0.18)'],
    ];
    $sectionTone = function (int $count) use ($sectionMax): array {
        $ratio = $sectionMax > 0 ? $count / $sectionMax : 0;
        if ($ratio >= 0.75) {
            return ['bar' => '#b91c1c', 'text' => 'text-red-700', 'track' => 'rgba(185, 28, 28, 0.16)'];
        }
        if ($ratio >= 0.5) {
            return ['bar' => '#ea580c', 'text' => 'text-orange-700', 'track' => 'rgba(234, 88, 12, 0.16)'];
        }
        if ($ratio >= 0.25) {
            return ['bar' => '#d97706', 'text' => 'text-amber-700', 'track' => 'rgba(217, 119, 6, 0.16)'];
        }

        return ['bar' => '#0f766e', 'text' => 'text-teal-700', 'track' => 'rgba(15, 118, 110, 0.16)'];
    };
@endphp

<style>
    .risk-kicker {
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--theme-muted);
    }
    .risk-note { margin-top: 0.35rem; font-size: 0.8125rem; line-height: 1.45; color: var(--theme-muted); }
    .risk-label { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 600; color: var(--theme-muted); }
    .risk-chart { position: relative; height: 16rem; }
    .risk-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.28rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
        background: color-mix(in srgb, var(--theme-bg) 55%, var(--theme-panel));
        color: var(--theme-text);
        border: 1px solid var(--theme-border);
    }
    .risk-meter { height: 0.4rem; border-radius: 999px; overflow: hidden; }
    .risk-meter > span { display: block; height: 100%; border-radius: 999px; min-width: 0; transition: width 0.25s ease; }
</style>

<div class="ui-panel mb-6 overflow-hidden">
    <div class="px-5 pt-5 pb-4 sm:px-6 border-b border-gray-100" style="background: linear-gradient(180deg, color-mix(in srgb, var(--theme-primary) 9%, var(--theme-panel)), var(--theme-panel));">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <div class="flex items-center gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" style="color: var(--theme-primary);">CSUCC · CEIT</p>
                    <span class="ui-chip ui-chip-info ui-live-dot">Live file</span>
                </div>
                <h3 class="ui-page-title mt-1">Student at-risk analysis</h3>
                <p class="ui-page-sub">Risk levels come from GPA, failed subjects, and enrollment standing. SPR family and income details explain possible circumstances. They do not raise a student's risk level.</p>
            </div>
            @if(auth()->user()?->canAccess('at_risk.manage'))
            <form method="POST" action="{{ route('at-risk.warnings') }}" class="shrink-0">
                @csrf
                <button type="submit" class="ui-btn-primary">Check warning thresholds</button>
            </form>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="risk-chip">GPA 2.75 or worse</span>
            <span class="risk-chip">Two or more failed subjects</span>
            <span class="risk-chip">Dropped, withdrawn, or stopped</span>
        </div>
        <p class="risk-note">A matching alert is sent once every 14 days. Access is limited to staff who can view student records.</p>
    </div>
</div>

<section class="mb-7">
    <p class="risk-kicker">How the file is classified</p>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="ui-kpi">
            <p class="ui-kpi-label">Students</p>
            <p class="ui-kpi-value">{{ number_format($summary['total']) }}</p>
            <p class="risk-note">{{ number_format($summary['enrolled']) }} currently enrolled</p>
        </div>
        @foreach($levelTones as $key => $tone)
        <div class="ui-kpi" style="border-left-color: {{ $tone['border'] }};">
            <p class="ui-kpi-label">{{ $tone['label'] }}</p>
            <p class="ui-kpi-value {{ $tone['text'] }}">{{ number_format($summary['counts'][$key]) }}</p>
            <p class="risk-note">{{ $share((int) $summary['counts'][$key]) }}</p>
        </div>
        @endforeach
    </div>
</section>

<section class="mb-7">
    <p class="risk-kicker">What the records show</p>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="ui-kpi" style="border-left-color: #b45309;">
            <p class="ui-kpi-label">Declining grades</p>
            <p class="ui-kpi-value">{{ number_format($summary['declining']) }}</p>
            <p class="risk-note">GPA worse than the previous term</p>
        </div>
        <div class="ui-kpi" style="border-left-color: #b45309;">
            <p class="ui-kpi-label">Frequent absences</p>
            <p class="ui-kpi-value">{{ number_format($summary['frequent_absences']) }}</p>
            <p class="risk-note">Attendance under 85%</p>
        </div>
        <div class="ui-kpi" style="border-left-color: #0b5d3b;">
            <p class="ui-kpi-label">Improved after difficulty</p>
            <p class="ui-kpi-value">{{ number_format($summary['improved']) }}</p>
            <p class="risk-note">Better standing than the prior term</p>
        </div>
        <div class="ui-kpi">
            <p class="ui-kpi-label">Interventions</p>
            <p class="ui-kpi-value">{{ number_format(array_sum($summary['outcomes'])) }}</p>
            <p class="risk-note">{{ number_format($summary['outcomes']['improved']) }} improved · {{ number_format($summary['outcomes']['pending']) }} pending</p>
        </div>
    </div>
</section>

<div class="ui-panel mb-6 overflow-hidden">
    <div class="px-5 py-4 sm:px-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" style="background: linear-gradient(180deg, color-mix(in srgb, #0f766e 8%, var(--theme-panel)), var(--theme-panel));">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-teal-800">Separate from the yearly forecast</p>
            <h3 class="text-base font-semibold mt-1">Random Forest attrition estimate</h3>
            <p class="risk-note">The student model, its counts, and the flagged list open on their own page.</p>
        </div>
        <a href="{{ route('at-risk.attrition.show') }}" class="ui-btn-primary shrink-0">Open</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold">Risk classification</h3>
        <p class="risk-note mb-3">One slice for each risk level in the full file.</p>
        <div class="risk-chart">
            <canvas id="riskChart"></canvas>
        </div>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold">Risk by year level</h3>
        <p class="risk-note mb-3">Stacked so a year level can be compared at a glance.</p>
        <div class="risk-chart">
            <canvas id="yearChart"></canvas>
        </div>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold">Circumstances among at-risk students</h3>
        <p class="risk-note mb-3">Counted only for moderate, high, and critical students. These figures are context, not a reason for the classification.</p>
        <div class="risk-chart">
            <canvas id="factorChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold">Common academic indicators</h3>
        <p class="risk-note mb-4">Counted among students above low risk.</p>
        <dl class="space-y-3">
            @foreach($summary['indicators'] as $label => $count)
            @php
                $tone = $indicatorTones[$label] ?? ['bar' => '#0f766e', 'text' => 'text-teal-700', 'track' => 'rgba(15, 118, 110, 0.16)'];
                $pct = round(((int) $count) / $indicatorMax * 100);
            @endphp
            <div>
                <div class="flex justify-between gap-4 text-sm mb-1">
                    <dt class="text-gray-600">{{ $label }}</dt>
                    <dd class="font-semibold tabular-nums {{ $tone['text'] }}">{{ number_format($count) }}</dd>
                </div>
                <div class="risk-meter" style="background: {{ $tone['track'] }};">
                    <span style="width: {{ $pct }}%; background: {{ $tone['bar'] }};"></span>
                </div>
            </div>
            @endforeach
        </dl>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold">At-risk students by section</h3>
        <p class="risk-note mb-4">Sections with no student above low risk are left out.</p>
        <dl class="space-y-3 max-h-72 overflow-y-auto pr-1">
            @forelse(collect($summary['by_section'])->filter()->sortDesc() as $section => $count)
            @php
                $tone = $sectionTone((int) $count);
                $pct = round(((int) $count) / $sectionMax * 100);
            @endphp
            <div>
                <div class="flex justify-between gap-4 text-sm mb-1">
                    <dt class="text-gray-600">{{ $section }}</dt>
                    <dd class="font-semibold tabular-nums {{ $tone['text'] }}">{{ number_format($count) }}</dd>
                </div>
                <div class="risk-meter" style="background: {{ $tone['track'] }};">
                    <span style="width: {{ $pct }}%; background: {{ $tone['bar'] }};"></span>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500">No section has a student above low risk.</p>
            @endforelse
        </dl>
    </div>
</div>

@if(!empty($cohortHeatmap['rows']))
<div class="ui-panel p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800">Diagnostics</span>
                <h3 class="text-base font-semibold text-gray-900">Subject Difficulty Heatmap by Course & Year</h3>
            </div>
            <p class="risk-note">Average number of failed subjects by program and year level. Darker red indicates curriculum bottlenecks where students struggle most.</p>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-gray-500 self-start sm:self-auto">
            <span>Low Risk</span>
            <span class="inline-block w-3.5 h-3.5 rounded" style="background: #ecfdf5; border: 1px solid #a7f3d0;"></span>
            <span class="inline-block w-3.5 h-3.5 rounded" style="background: #fee2e2; border: 1px solid #fca5a5;"></span>
            <span class="inline-block w-3.5 h-3.5 rounded" style="background: #b91c1c;"></span>
            <span>Critical</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-200">
                    <th class="py-2 pr-3 font-semibold">Course</th>
                    @foreach(($cohortHeatmap['levels'] ?? [1, 2, 3, 4]) as $level)
                        <th class="py-2 px-2 font-semibold">Year {{ $level }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(($cohortHeatmap['rows'] ?? []) as $row)
                <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                    <td class="py-2 pr-3 font-semibold text-gray-800">{{ $row['program'] }}</td>
                    @foreach($cohortHeatmap['levels'] as $level)
                        @php
                            $cell = $row['cells'][$level] ?? null;
                            $failed = $cell['failed'] ?? 0;
                            $max = max(0.01, (float) ($cohortHeatmap['max'] ?? 1));
                            $mix = (int) round(($failed / $max) * 75);
                        @endphp
                        <td class="py-1.5 px-2">
                            <span class="inline-flex min-w-16 justify-center rounded px-2.5 py-1 text-xs font-semibold"
                                  style="background: color-mix(in srgb, #b91c1c {{ $mix }}%, #ecfdf5); color: {{ $mix > 45 ? '#ffffff' : '#1f2937' }};"
                                  title="{{ $cell['students'] ?? 0 }} students enrolled">
                                {{ $cell ? number_format($failed, 2) : '—' }}
                            </span>
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="ui-panel overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold">Student list</h3>
        <p class="risk-note">The full file stays in the database. This table draws one page at a time.</p>
    </div>
    @php
        $atRiskChips = collect([
            request('q') ? ['key' => 'q', 'label' => 'ID: '.request('q')] : null,
            request('level') ? ['key' => 'level', 'label' => \App\Models\StudentRecord::RISK_LEVELS[request('level')] ?? request('level')] : null,
            request('year_level') ? ['key' => 'year_level', 'label' => \App\Models\EnrollmentRecord::yearLevelLabel((int) request('year_level'))] : null,
            request('program_id') ? ['key' => 'program_id', 'label' => $programs[(int) request('program_id')] ?? ('Course #'.request('program_id'))] : null,
        ])->filter()->values();
    @endphp
    <form method="GET" action="{{ route('at-risk.index') }}" class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end border-b border-gray-100 sticky top-2 z-10" style="backdrop-filter: blur(8px); background: color-mix(in srgb, var(--theme-panel) 94%, transparent);">
        <label class="lg:col-span-2">
            <span class="risk-label">Student ID</span>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search an ID" class="ui-input">
        </label>
        <label>
            <span class="risk-label">Risk level</span>
            <select name="level" class="ui-input">
                <option value="">All risk levels</option>
                @foreach(\App\Models\StudentRecord::RISK_LEVELS as $value => $label)
                    <option value="{{ $value }}" @selected(request('level') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="risk-label">Year level</span>
            <select name="year_level" class="ui-input">
                <option value="">All year levels</option>
                @foreach([1, 2, 3, 4, 5] as $year)
                    <option value="{{ $year }}" @selected((string) request('year_level') === (string) $year)>{{ \App\Models\EnrollmentRecord::yearLevelLabel($year) }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="risk-label">Course</span>
            <select name="program_id" class="ui-input">
                <option value="">All courses</option>
                @foreach($programs as $id => $code)
                    <option value="{{ $id }}" @selected((string) request('program_id') === (string) $id)>{{ $code }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="ui-btn-filter">Filter</button>
            <a href="{{ route('at-risk.index') }}" class="ui-btn-clear">Clear</a>
        </div>
        @if($atRiskChips->isNotEmpty())
        <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center gap-2 -mt-1">
            <span class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Active filters</span>
            @foreach($atRiskChips as $chip)
                <a href="{{ route('at-risk.index', request()->except($chip['key'])) }}" class="ui-chip ui-chip-info hover:opacity-80">
                    {{ $chip['label'] }} <span class="font-bold">×</span>
                </a>
            @endforeach
            <a href="{{ route('at-risk.index') }}" class="text-xs font-semibold text-amber-800 underline">Clear all</a>
        </div>
        @endif
    </form>
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr>
                    <th class="text-left">Student</th>
                    <th class="text-left">Course</th>
                    <th class="text-left">GPA</th>
                    <th class="text-left">Failed</th>
                    <th class="text-left">Risk</th>
                    <th class="text-left">Why</th>
                    <th class="text-right">Report</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $row)
                <tr>
                    <td>
                        <p class="font-mono text-sm font-semibold">{{ $row['student_id'] }}</p>
                    </td>
                    <td>{{ $row['program'] }} · {{ $row['year_label'] }} {{ $row['section'] }}</td>
                    <td class="tabular-nums">{{ number_format($row['gpa'], 2) }}</td>
                    <td>
                        @if($row['failed_subjects'] > 0)
                            <div class="flex flex-col">
                                <span class="inline-flex items-center gap-1 font-bold text-red-600 text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                    {{ $row['failed_subjects'] }} failed
                                </span>
                                @if(!empty($row['struggling_subjects']))
                                    <span class="text-[11px] text-red-700 font-medium max-w-[200px] truncate" title="{{ $row['struggling_subjects'] }}">
                                        {{ $row['struggling_subjects'] }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-500 font-mono">0</span>
                        @endif
                    </td>
                    <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\StudentRecord::query()->make(['risk_level' => $row['level']])->riskBadgeClass() }}">{{ $row['level_label'] }}</span></td>
                    <td class="text-xs text-gray-700 max-w-[220px]">{{ $row['why'] ?? '—' }}</td>
                    <td class="text-right"><a href="{{ route('at-risk.show', $row['student_record_id']) }}" class="ui-link text-xs">Open</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <p class="text-base font-bold text-gray-800 mb-1">No students match this filter</p>
                        <p class="text-sm text-gray-500 mb-4">Try another risk level or course, or clear filters to see the full file.</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <a href="{{ route('at-risk.index') }}" class="ui-btn-clear">Clear filters</a>
                            <a href="{{ route('students.index') }}" class="ui-btn-secondary">Student Records</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100">
        <p class="text-xs text-gray-500">Showing {{ $students->count() }} of {{ number_format($students->total()) }} matching students.</p>
        {{ $students->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    const riskColors = @json($levelColors);
    new Chart(document.getElementById('riskChart'), {
        type: 'doughnut',
        data: {
            labels: ['Low', 'Moderate', 'High', 'Critical'],
            datasets: [{ data: @json(array_values($summary['counts'])), backgroundColor: riskColors, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
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
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
    });
    new Chart(document.getElementById('factorChart'), {
        type: 'bar',
        data: {
            labels: @json(array_values($factorLabels)),
            datasets: [{ label: 'At-risk students', data: @json(array_values($summary['factors'])), backgroundColor: '#0f766e', borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>
@endpush
