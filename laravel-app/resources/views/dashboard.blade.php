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
                <option value="">All colleges</option>
                @foreach($colleges ?? [] as $college)
                    <option value="{{ $college->id }}" @selected(($filters['college_id'] ?? '') == $college->id)>{{ $college->college_name }}</option>
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
        <button class="ui-btn-primary h-[2.45rem]">Apply filters</button>
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
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Total enrollment</p>
            <p class="ui-kpi-value">{{ number_format($totalEnrollment ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $totalChangeClass }}">{{ $totalChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">New students</p>
            <p class="ui-kpi-value">{{ number_format($newStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $newChangeClass }}">{{ $newChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Returning / continuing</p>
            <p class="ui-kpi-value">{{ number_format($returningStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $returningChangeClass }}">{{ $returningChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Retention rate</p>
            <p class="ui-kpi-value">{{ $retentionRate ?? 0 }}%</p>
            <p class="dash-note">{{ empty($previousAY) ? 'No prior year to compare' : 'Returning and continuing students divided by the '.$previousAY->academic_year.' total' }}</p>
        </div>
    </div>
</section>

<section class="dash-section">
    <p class="dash-kicker">Students who moved or left</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="ui-kpi dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Shiftees</p>
            <p class="ui-kpi-value">{{ number_format($shifteeStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $shifteeChangeClass }}">{{ $shifteeChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Transferred</p>
            <p class="ui-kpi-value">{{ number_format($transferredStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $transferredChangeClass }}">{{ $transferredChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Withdrawn</p>
            <p class="ui-kpi-value">{{ number_format($withdrawnStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $withdrawnChangeClass }}">{{ $withdrawnChange }}</p>
        </div>
        <div class="ui-kpi dash-kpi dash-kpi-watch">
            <p class="ui-kpi-label">Dropped</p>
            <p class="ui-kpi-value">{{ number_format($droppedStudents ?? 0) }}</p>
            <p class="mt-2 text-sm font-medium {{ $droppedChangeClass }}">{{ $droppedChange }}</p>
        </div>
    </div>
</section>

<section class="dash-section">
    <p class="dash-kicker">Programs and the next forecast</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Highest-enrolled program</p>
            <p class="mt-2 text-lg font-bold leading-snug">{{ $highestProgram['program_name'] ?? 'N/A' }}</p>
            <p class="dash-note">{{ number_format($highestProgram['current'] ?? 0) }} students in this view</p>
        </div>
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Lowest-enrolled program</p>
            <p class="mt-2 text-lg font-bold leading-snug">{{ $lowestProgram['program_name'] ?? 'N/A' }}</p>
            <p class="dash-note">{{ number_format($lowestProgram['current'] ?? 0) }} students in this view</p>
        </div>
        <div class="ui-kpi dash-kpi">
            <p class="ui-kpi-label">Forecasted next period</p>
            <p class="ui-kpi-value">{{ $forecast ? number_format($forecast->predicted_enrollment) : '—' }}</p>
            <p class="dash-note">{{ $forecast ? $forecast->model_used.' · institution forecast' : 'Run an institution forecast to show a number here' }}</p>
        </div>
        <div class="ui-kpi dash-kpi">
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Top programs</h3>
        <p class="dash-note mb-4">Programs with the most students in the selected term.</p>
        <div class="dash-chart-short">
            <canvas id="programChart"></canvas>
        </div>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold mb-1">Enrollment by college</h3>
        <p class="dash-note mb-4">Share of the selected term by college.</p>
        <div class="dash-chart-short">
            <canvas id="collegeChart"></canvas>
        </div>
    </div>
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
    const themePrimary = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim() || '#0b5d3b';
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
    new Chart(document.getElementById('programChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($topPrograms ?? [])->pluck('program_code')),
            datasets: [{ label: 'Students', data: @json(collect($topPrograms ?? [])->pluck('current')), backgroundColor: themePrimary, borderRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.25)' } }, y: { grid: { display: false } } }
        }
    });
    new Chart(document.getElementById('collegeChart'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($collegeRows ?? [])->pluck('college_name')),
            datasets: [{ data: @json(collect($collegeRows ?? [])->pluck('total')), backgroundColor: [themePrimary, '#d4a017', '#0f766e', '#b45309', '#334155'], borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            cutout: '62%'
        }
    });
</script>
@endpush
