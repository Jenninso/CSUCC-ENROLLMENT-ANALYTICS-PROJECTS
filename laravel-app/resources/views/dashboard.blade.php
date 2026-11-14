@extends('layouts.app')
@section('title', 'Dashboard')
@section('hidePageHead', '1')

@section('content')
<div class="ui-panel mb-6 overflow-hidden">
    <div class="px-5 pt-5 pb-4 sm:px-6 border-b border-gray-100" style="background: linear-gradient(180deg, color-mix(in srgb, var(--theme-primary) 9%, var(--theme-panel)), var(--theme-panel));">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" style="color: var(--theme-primary);">CEIT Program</p>
        <h3 class="ui-page-title mt-1">Enrollment overview</h3>
        <p class="ui-page-sub">Monitor CEIT Program enrollment, retention, and planning signals for the selected term.</p>
    </div>
<form method="GET" class="p-4 sm:p-5 grid grid-cols-2 lg:grid-cols-6 gap-3 items-end">
    <select name="academic_year_id" class="ui-input">
        <option value="">Latest academic year</option>
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
    <button class="ui-btn-primary h-[2.45rem]">Apply filters</button>
</form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
    <div class="ui-kpi">
        <p class="ui-kpi-label">Total Enrollment</p>
        <p class="ui-kpi-value">{{ number_format($totalEnrollment ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $totalChangeClass }}">{{ $totalChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">New Students</p>
        <p class="ui-kpi-value">{{ number_format($newStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $newChangeClass }}">{{ $newChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Returning / Continuing</p>
        <p class="ui-kpi-value">{{ number_format($returningStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $returningChangeClass }}">{{ $returningChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Retention Rate</p>
        <p class="ui-kpi-value">{{ $retentionRate ?? 0 }}%</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ empty($previousAY) ? 'No prior year to compare' : 'Returning + continuing ÷ '.$previousAY->academic_year.' total' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Shiftees</p>
        <p class="ui-kpi-value">{{ number_format($shifteeStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $shifteeChangeClass }}">{{ $shifteeChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Transferred</p>
        <p class="ui-kpi-value">{{ number_format($transferredStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $transferredChangeClass }}">{{ $transferredChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Withdrawn</p>
        <p class="ui-kpi-value">{{ number_format($withdrawnStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $withdrawnChangeClass }}">{{ $withdrawnChange }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Drop</p>
        <p class="ui-kpi-value">{{ number_format($droppedStudents ?? 0) }}</p>
        <p class="mt-2 text-sm font-medium {{ $droppedChangeClass }}">{{ $droppedChange }}</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Highest-enrolled program</p>
        <p class="mt-2 text-lg font-bold text-gray-800 leading-snug">{{ $highestProgram['program_name'] ?? 'N/A' }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ number_format($highestProgram['current'] ?? 0) }} students</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Lowest-enrolled program</p>
        <p class="mt-2 text-lg font-bold text-gray-800 leading-snug">{{ $lowestProgram['program_name'] ?? 'N/A' }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ number_format($lowestProgram['current'] ?? 0) }} students</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Forecasted next period</p>
        <p class="ui-kpi-value">{{ $forecast ? number_format($forecast->predicted_enrollment) : '—' }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ $forecast->model_used ?? 'Run forecasting to generate' }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Unread alerts</p>
        <p class="ui-kpi-value">{{ $unreadAlerts ?? 0 }}</p>
    </div>
</div>

<div class="ui-panel p-5 mb-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">Enrollment trend</h3>
    <canvas id="enrollmentTrendChart" height="100"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Top programs</h3>
        <canvas id="programChart" height="200"></canvas>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Enrollment by college</h3>
        <canvas id="collegeChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Recent AI insights</h3>
        @forelse($insights ?? [] as $insight)
        <div class="border-b border-gray-100 last:border-0 py-3">
            <p class="text-sm font-medium text-gray-800">{{ $insight->title }}</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $insight->evidence }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-400">Generate insights from AI Decision Support after importing data.</p>
        @endforelse
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Recent alerts</h3>
        @forelse($alerts ?? [] as $alert)
        <div class="border-b border-gray-100 last:border-0 py-3">
            <p class="text-sm font-medium text-gray-800">{{ $alert->title }} <span class="text-xs uppercase text-gray-500">({{ $alert->severity }})</span></p>
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
    new Chart(document.getElementById('enrollmentTrendChart'), {
        type: 'line',
        data: {
            labels: @json(collect($trends ?? [])->pluck('label')),
            datasets: [{ label: 'Enrollment', data: @json(collect($trends ?? [])->pluck('total')), borderColor: themePrimary, backgroundColor: 'rgba(11,93,59,0.10)', fill: true, tension: 0.35 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }
    });
    new Chart(document.getElementById('programChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($topPrograms ?? [])->pluck('program_code')),
            datasets: [{ label: 'Students', data: @json(collect($topPrograms ?? [])->pluck('current')), backgroundColor: themePrimary }]
        },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('collegeChart'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($collegeRows ?? [])->pluck('college_name')),
            datasets: [{ data: @json(collect($collegeRows ?? [])->pluck('total')), backgroundColor: [themePrimary, '#d4a017', '#0f766e', '#b45309', '#334155'] }]
        },
        options: { responsive: true }
    });
</script>
@endpush
