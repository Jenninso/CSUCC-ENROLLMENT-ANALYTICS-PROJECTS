@extends('layouts.app')
@section('title', 'Dashboard')
@section('hidePageHead', '1')

@section('content')
<style>
    .dash-section { margin-bottom: 1.75rem; }
    .dash-kicker {
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--theme-muted);
    }
    .dash-kpi { min-height: 7.25rem; }
    .dash-kpi-watch { border-left-color: #b45309; }
    .dash-note { margin-top: 0.35rem; font-size: 0.8125rem; line-height: 1.4; color: var(--theme-muted); }
    .dash-chart { position: relative; height: 18rem; }
    .dash-chart-short { position: relative; height: 16rem; }
    .dash-label { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 600; color: var(--theme-muted); }
</style>

<div class="ui-panel mb-6 overflow-hidden">
    <div class="px-5 pt-5 pb-4 sm:px-6 border-b border-gray-100" style="background: linear-gradient(180deg, color-mix(in srgb, var(--theme-primary) 9%, var(--theme-panel)), var(--theme-panel));">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" style="color: var(--theme-primary);">CEIT Program</p>
        <h3 class="ui-page-title mt-1">Enrollment overview</h3>
        <p class="ui-page-sub">The numbers below follow the filters. Change the term, then apply, to read a different slice of the same records.</p>
    </div>
    <form method="GET" class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <label>
            <span class="dash-label">Academic year</span>
            <select name="academic_year_id" class="ui-input">
                <option value="">Latest academic year</option>
                @foreach($academicYears ?? [] as $ay)
                    <option value="{{ $ay->id }}" @selected(($filters['academic_year_id'] ?? '') == $ay->id)>{{ $ay->academic_year }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="dash-label">Semester</span>
            <select name="semester_name" class="ui-input">
                <option value="">All semesters</option>
                @foreach($semesters ?? [] as $sem)
                    <option value="{{ $sem->semester_name }}" @selected(($filters['semester_name'] ?? '') === $sem->semester_name)>{{ $sem->semester_name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="dash-label">College</span>
            <select name="college_id" class="ui-input">
                @foreach($colleges ?? [] as $college)
                    <option value="{{ $college->id }}" @selected((string) ($filters['college_id'] ?? $college->id) === (string) $college->id)>{{ $college->college_code }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="dash-label">Program</span>
            <select name="program_id" class="ui-input">
                <option value="">All programs</option>
                @foreach($programs ?? [] as $program)
                    <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>{{ $program->program_code }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="dash-label">Year level</span>
            <select name="year_level" class="ui-input">
                <option value="">All year levels</option>
                @for($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}" @selected(($filters['year_level'] ?? '') == $i)>{{ \App\Models\EnrollmentRecord::yearLevelLabel($i) }}</option>
                @endfor
            </select>
        </label>
        <button class="ui-btn-filter h-[2.45rem]">Apply filters</button>
    </form>
</div>

@php
    $yearChange = function ($rate, bool $increaseIsGood = true) use ($previousAY) {
        if (empty($previousAY)) {
            return ['No prior year', 'text-gray-400'];
        }
        $rate = (float) ($rate ?? 0);
        if ($rate == 0.0) {
            return ['No change vs '.$previousAY->academic_year, 'text-gray-400'];
        }
        $up = $rate > 0;
        $favorable = $increaseIsGood ? $up : ! $up;

        return [
            ($up ? 'Up' : 'Down').' '.abs($rate).'% vs '.$previousAY->academic_year,
            $favorable ? 'text-emerald-600' : 'text-red-600',
        ];
    };
    [$totalChange, $totalChangeClass] = $yearChange($growthRate ?? 0);
    [$newChange, $newChangeClass] = $yearChange($newStudentsGrowth ?? 0);
    [$returningChange, $returningChangeClass] = $yearChange($returningStudentsGrowth ?? 0);
    $studentShare = function ($rate) use ($totalEnrollment) {
        return ((float) $rate).'% of '.number_format($totalEnrollment ?? 0).' students';
    };
    $countTone = function ($count) {
        return ($count ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600';
    };
    $shifteeChange = $studentShare($shifteeRate ?? 0);
    $shifteeChangeClass = $countTone($shifteeStudents ?? 0);
    $transferredChange = $studentShare($transferredRate ?? 0);
    $transferredChangeClass = $countTone($transferredStudents ?? 0);
    $withdrawnChange = $studentShare($withdrawnRate ?? 0);
    $withdrawnChangeClass = $countTone($withdrawnStudents ?? 0);
    $droppedChange = $studentShare($droppedRate ?? 0);
    $droppedChangeClass = $countTone($droppedStudents ?? 0);
@endphp

<section class="dash-section">
    <p class="dash-kicker">This term</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="ui-kpi ui-kpi-info dash-kpi">
            <p class="ui-kpi-label">Total enrollment</p>
            <p class="ui-kpi-value">{{ number_format($totalEnrollment ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $totalChangeClass }}">{{ $totalChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-good dash-kpi">
            <p class="ui-kpi-label">New students</p>
            <p class="ui-kpi-value">{{ number_format($newStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $newChangeClass }}">{{ $newChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-sky dash-kpi">
            <p class="ui-kpi-label">Returning / continuing</p>
            <p class="ui-kpi-value">{{ number_format($returningStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $returningChangeClass }}">{{ $returningChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-good dash-kpi">
            <p class="ui-kpi-label">Retention rate</p>
            <p class="ui-kpi-value">{{ $retentionRate ?? 0 }}%</p>
            <p class="dash-note">{{ empty($previousAY) ? 'No prior year to compare' : 'Returning and continuing students divided by the '.$previousAY->academic_year.' total' }}</p>
        </div>
    </div>
</section>

<section class="dash-section">
    <p class="dash-kicker">Students who moved or left</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="ui-kpi ui-kpi-warn dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Shiftees</p>
            <p class="ui-kpi-value">{{ number_format($shifteeStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $shifteeChangeClass }}">{{ $shifteeChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-alert dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Transferred</p>
            <p class="ui-kpi-value">{{ number_format($transferredStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $transferredChangeClass }}">{{ $transferredChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-bad dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Withdrawn</p>
            <p class="ui-kpi-value">{{ number_format($withdrawnStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $withdrawnChangeClass }}">{{ $withdrawnChange }}</p>
        </div>
        <div class="ui-kpi ui-kpi-bad dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Dropped</p>
            <p class="ui-kpi-value">{{ number_format($droppedStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $droppedChangeClass }}">{{ $droppedChange }}</p>
        </div>
    </div>
</section>

<section class="dash-section">
    <p class="dash-kicker">Programs and the next forecast</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="ui-kpi ui-kpi-info dash-kpi">
            <p class="ui-kpi-label">Highest-enrolled program</p>
            <p class="mt-2 text-lg font-bold leading-snug">{{ $highestProgram['program_name'] ?? 'N/A' }}</p>
            <p class="dash-note">{{ number_format($highestProgram['current'] ?? 0) }} students in this view</p>
        </div>
        <div class="ui-kpi ui-kpi-warn dash-kpi">
            <p class="ui-kpi-label">Lowest-enrolled program</p>
            <p class="mt-2 text-lg font-bold leading-snug">{{ $lowestProgram['program_name'] ?? 'N/A' }}</p>
            <p class="dash-note">{{ number_format($lowestProgram['current'] ?? 0) }} students in this view</p>
        </div>
        <div class="ui-kpi ui-kpi-sky dash-kpi">
            <p class="ui-kpi-label">Forecasted next period</p>
            <p class="ui-kpi-value">{{ $forecast ? number_format($forecast->predicted_enrollment) : '—' }}</p>
            <p class="dash-note">{{ $forecast ? $forecast->model_used.' · institution forecast' : 'Run an institution forecast to show a number here' }}</p>
        </div>
        <div class="ui-kpi {{ ($unreadAlerts ?? 0) > 0 ? 'ui-kpi-alert' : 'ui-kpi-good' }} dash-kpi">
            <p class="ui-kpi-label">Unread alerts</p>
            <p class="ui-kpi-value">{{ $unreadAlerts ?? 0 }}</p>
            <p class="dash-note">{{ ($unreadAlerts ?? 0) > 0 ? 'Open Alerts to review them' : 'No unread alerts' }}</p>
        </div>
    </div>
</section>

<div class="ui-panel p-5 mb-6">
    <h3 class="text-base font-semibold mb-1">Enrollment trend</h3>
    <p class="dash-note mb-4">Recorded headcount across academic years. The line uses the same filters as the cards above.</p>
    <div class="dash-chart">
        <canvas id="enrollmentTrendChart"></canvas>
    </div>
</div>

@php
    $programChartRows = collect($topPrograms ?? [])->filter(fn ($row) => (int) ($row['current'] ?? 0) > 0)->values();
    $collegeChartRows = collect($collegeRows ?? [])->filter(fn ($row) => (int) ($row['total'] ?? 0) > 0)->values();
@endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Top programs</h3>
        <p class="dash-note mb-4">Programs with the most students in the selected term.</p>
        @if($programChartRows->isEmpty())
            <div class="dash-chart-short flex items-center justify-center rounded-xl border border-dashed border-gray-200 bg-slate-50">
                <p class="text-sm text-gray-500 px-4 text-center">No program enrollment yet for this term. Import student records to populate this chart.</p>
            </div>
        @else
            <div class="dash-chart-short">
                <canvas id="programChart"></canvas>
            </div>
        @endif
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Enrollment by college</h3>
        <p class="dash-note mb-4">Share of the selected term by college.</p>
        @if($collegeChartRows->isEmpty())
            <div class="dash-chart-short flex items-center justify-center rounded-xl border border-dashed border-gray-200 bg-slate-50">
                <p class="text-sm text-gray-500 px-4 text-center">No college enrollment share yet for this term.</p>
            </div>
        @else
            <div class="dash-chart-short">
                <canvas id="collegeChart"></canvas>
            </div>
        @endif
    </div>
</div>

<div class="ui-panel p-5 mb-6 overflow-x-auto">
    <h3 class="text-base font-semibold mb-1">Subject difficulty</h3>
    <p class="dash-note mb-4">Each cell is the average number of failed subjects for that course and year. A darker cell means that group is struggling with more subjects. Read it across one row to compare year levels at a glance.</p>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500">
                <th class="py-2 pr-3">Course</th>
                @foreach(($subjectDifficulty['levels'] ?? []) as $level)
                    <th class="py-2 px-2">Year {{ $level }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($subjectDifficulty['rows'] ?? [] as $row)
                <tr>
                    <td class="py-2 pr-3 font-semibold">{{ $row['program'] }}</td>
                    @foreach($subjectDifficulty['levels'] as $level)
                        @php
                            $cell = $row['cells'][$level] ?? null;
                            $failed = $cell['failed'] ?? 0;
                            $max = max(0.01, (float) ($subjectDifficulty['max'] ?? 1));
                            $mix = (int) round(($failed / $max) * 70);
                        @endphp
                        <td class="py-2 px-2">
                            <span class="inline-flex min-w-16 justify-center rounded-md px-2 py-1 text-xs font-semibold" style="background: color-mix(in srgb, #b91c1c {{ $mix }}%, #ecfdf5);" title="{{ $cell['students'] ?? 0 }} students">
                                {{ $cell ? number_format($failed, 2) : '—' }}
                            </span>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="py-3 text-gray-500" colspan="5">No student records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Recent AI insights</h3>
        <p class="dash-note mb-3">Notes generated from the enrollment figures. They do not change the records.</p>
        @forelse($insights ?? [] as $insight)
        <div class="border-b border-gray-100 last:border-0 py-3">
            <p class="text-sm font-medium">{{ $insight->title }}</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $insight->evidence }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-400">Generate insights from AI Decision Support after importing data.</p>
        @endforelse
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Recent alerts</h3>
        <p class="dash-note mb-3">The latest notices, including ones already read.</p>
        @forelse($alerts ?? [] as $alert)
        <div class="border-b border-gray-100 last:border-0 py-3">
            <p class="text-sm font-medium">{{ $alert->title }} <span class="text-xs uppercase text-gray-500">({{ $alert->severity }})</span></p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $alert->created_at?->diffForHumans() }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-400">No alerts yet. Run alert evaluation from the Alerts page.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    function resolveChartColor() {
        const styles = getComputedStyle(document.documentElement);
        const candidates = [
            styles.getPropertyValue('--theme-link').trim(),
            styles.getPropertyValue('--csucc-green').trim(),
            styles.getPropertyValue('--theme-primary').trim(),
            '#0b5d3b',
        ].filter(Boolean);

        const isTooLight = (value) => {
            const raw = String(value).trim().toLowerCase();
            if (!raw || raw === 'white' || raw === 'transparent') return true;
            const hex = raw.replace('#', '');
            if (!/^[0-9a-f]{3}$|^[0-9a-f]{6}$/i.test(hex)) return false;
            const full = hex.length === 3 ? hex.split('').map((c) => c + c).join('') : hex;
            const r = parseInt(full.slice(0, 2), 16);
            const g = parseInt(full.slice(2, 4), 16);
            const b = parseInt(full.slice(4, 6), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.82;
        };

        return candidates.find((color) => !isTooLight(color)) || '#0b5d3b';
    }

    const themePrimary = resolveChartColor();
    const chartPalette = [themePrimary, '#d4a017', '#0f766e', '#b45309', '#334155', '#0369a1', '#7c3aed'];
    const axis = {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { beginAtZero: true, ticks: { color: '#64748b' }, grid: { color: 'rgba(148, 163, 184, 0.25)' } }
    };
    new Chart(document.getElementById('enrollmentTrendChart'), {
        type: 'line',
        data: {
            labels: @json(collect($trends ?? [])->pluck('label')),
            datasets: [{
                label: 'Enrollment',
                data: @json(collect($trends ?? [])->pluck('total')),
                borderColor: themePrimary,
                backgroundColor: 'rgba(11, 93, 59, 0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: themePrimary,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: axis
        }
    });
    @if($programChartRows->isNotEmpty())
    (() => {
        const programData = @json($programChartRows->pluck('current')->values());
        new Chart(document.getElementById('programChart'), {
            type: 'bar',
            data: {
                labels: @json($programChartRows->pluck('program_code')->values()),
                datasets: [{
                    label: 'Students',
                    data: programData,
                    backgroundColor: programData.map((_, i) => chartPalette[i % chartPalette.length]),
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: '#64748b', precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.25)' }
                    },
                    y: {
                        ticks: { color: '#334155' },
                        grid: { display: false }
                    }
                }
            }
        });
    })();
    @endif
    @if($collegeChartRows->isNotEmpty())
    (() => {
        const collegeData = @json($collegeChartRows->pluck('total')->values());
        new Chart(document.getElementById('collegeChart'), {
            type: 'doughnut',
            data: {
                labels: @json($collegeChartRows->pluck('college_name')->values()),
                datasets: [{
                    data: collegeData,
                    backgroundColor: collegeData.map((_, i) => chartPalette[i % chartPalette.length]),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#334155', boxWidth: 12, usePointStyle: true }
                    }
                },
                cutout: '62%'
            }
        });
    })();
    @endif
</script>
@endpush
