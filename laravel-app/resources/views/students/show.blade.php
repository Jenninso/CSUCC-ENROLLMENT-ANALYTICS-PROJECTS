@extends('layouts.app')
@section('title', 'Student '.$student->student_id)
@section('hidePageHead', '1')

@section('content')
@php
    $riskTone = match ($student->risk_level) {
        'low' => ['kpi' => 'ui-kpi-good', 'bar' => '#0b5d3b', 'chip' => 'ui-chip-good'],
        'moderate' => ['kpi' => 'ui-kpi-warn', 'bar' => '#d97706', 'chip' => 'ui-chip-warn'],
        'high' => ['kpi' => 'ui-kpi-alert', 'bar' => '#ea580c', 'chip' => 'ui-chip-alert'],
        'critical' => ['kpi' => 'ui-kpi-bad', 'bar' => '#b91c1c', 'chip' => 'ui-chip-bad'],
        default => ['kpi' => 'ui-kpi-info', 'bar' => '#0f766e', 'chip' => 'ui-chip-info'],
    };
    $failedTone = $student->failed_subjects >= 3 ? 'ui-kpi-bad' : ($student->failed_subjects >= 1 ? 'ui-kpi-alert' : 'ui-kpi-good');
    $gpaTone = $student->gpa > 3 ? 'ui-kpi-bad' : ($student->gpa >= 2.75 ? 'ui-kpi-alert' : ($student->gpa >= 2.25 ? 'ui-kpi-warn' : 'ui-kpi-good'));
@endphp

<div class="page-head">
    <div>
        <a href="{{ route('students.index') }}" class="ui-link text-sm">← Back to student records</a>
        <p class="page-kicker mt-2">CSUCC · CEIT · Student record</p>
        <h3 class="ui-page-title font-mono">{{ $student->student_id }}</h3>
        <p class="ui-page-sub">Identified by Student ID only. No personal name is stored on this record.</p>
    </div>
    @if(auth()->user()?->canAccess('enrollment.manage'))
    <form method="POST" action="{{ route('students.destroy', $student) }}" class="shrink-0" onsubmit="return confirm('Delete the record for {{ $student->student_id }}?')">
        @csrf @method('DELETE')
        <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Delete</button>
    </form>
    @endif
</div>

{{-- Hero identity --}}
<div class="ui-panel overflow-hidden mb-4" style="border-left: 4px solid {{ $riskTone['bar'] }};">
    <div class="px-5 py-5 sm:px-6" style="background: linear-gradient(135deg, color-mix(in srgb, {{ $riskTone['bar'] }} 10%, var(--theme-panel)), var(--theme-panel) 55%);">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-[0.16em] font-bold text-gray-500">Student ID</p>
                <h3 class="text-2xl sm:text-3xl font-bold font-mono text-gray-900 mt-1 tracking-tight">{{ $student->student_id }}</h3>
                <p class="text-sm text-gray-600 mt-2">
                    <span class="font-semibold text-teal-800">{{ $student->program->program_code ?? '—' }}</span>
                    · {{ \App\Models\EnrollmentRecord::yearLevelLabel($student->year_level) }}
                    · Sec {{ $student->section }}
                    · {{ $student->academicYear->academic_year ?? '—' }} {{ $student->semester->semester_name ?? '' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="ui-chip {{ $riskTone['chip'] }}">{{ $student->riskLabel() }} risk</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $student->academicStandingBadgeClass() }}">{{ $student->academicStandingLabel() }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Snapshot meters --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="ui-kpi {{ $gpaTone }}">
        <p class="ui-kpi-label">GPA</p>
        <p class="ui-kpi-value tabular-nums">{{ number_format($student->gpa, 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">1.00 best · 5.00 fail</p>
    </div>
    <div class="ui-kpi ui-kpi-sky">
        <p class="ui-kpi-label">Average grade</p>
        <p class="ui-kpi-value tabular-nums">{{ number_format($student->average_grade, 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $student->subjects_count }} subjects</p>
    </div>
    <div class="ui-kpi {{ $failedTone }}">
        <p class="ui-kpi-label">Failed subjects</p>
        <p class="ui-kpi-value tabular-nums">{{ $student->failed_subjects }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ number_format($student->units_enrolled, 1) }} units enrolled</p>
    </div>
    <div class="ui-kpi {{ $riskTone['kpi'] }}">
        <p class="ui-kpi-label">Risk level</p>
        <p class="ui-kpi-value text-xl">{{ $student->riskLabel() }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $student->enrolledLabel() }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    {{-- Program & term --}}
    <section class="ui-panel overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100" style="background: linear-gradient(90deg, color-mix(in srgb, #0f766e 12%, var(--theme-panel)), var(--theme-panel));">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-teal-800">Program & term</p>
        </div>
        <dl class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="ui-kpi-label mb-1">College</dt>
                <dd class="font-semibold text-gray-900">{{ $student->program?->department?->college?->college_code ?? '—' }}</dd>
                <dd class="text-xs text-gray-500 mt-0.5">{{ $student->program?->department?->college?->college_name ?? '' }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Group</dt>
                <dd class="font-semibold text-gray-900">{{ $student->academicGroup() }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="ui-kpi-label mb-1">Course</dt>
                <dd class="font-semibold text-gray-900">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-teal-100 text-teal-900 mr-1.5">{{ $student->program->program_code ?? '—' }}</span>
                    {{ $student->program->program_name ?? '' }}
                </dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Year · Section</dt>
                <dd class="font-semibold text-gray-900">{{ \App\Models\EnrollmentRecord::yearLevelLabel($student->year_level) }} · {{ $student->section }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Academic year</dt>
                <dd class="font-semibold text-gray-900">{{ $student->academicYear->academic_year ?? '—' }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Semester</dt>
                <dd class="font-semibold text-gray-900">{{ $student->semester->semester_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Enrolled status</dt>
                <dd>
                    <span class="ui-chip {{ in_array($student->enrolled_status, ['enrolled'], true) ? 'ui-chip-good' : (in_array($student->enrolled_status, ['dropped', 'withdrawn'], true) ? 'ui-chip-bad' : 'ui-chip-warn') }}">
                        {{ $student->enrolledLabel() }}
                    </span>
                </dd>
            </div>
        </dl>
    </section>

    {{-- Profile & support --}}
    <section class="ui-panel overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100" style="background: linear-gradient(90deg, color-mix(in srgb, #0369a1 12%, var(--theme-panel)), var(--theme-panel));">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-sky-800">Profile & support</p>
        </div>
        <dl class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="ui-kpi-label mb-1">Gender</dt>
                <dd class="font-semibold text-gray-900">{{ $student->genderLabel() }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Age</dt>
                <dd class="font-semibold text-gray-900">{{ $student->age }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Student type</dt>
                <dd><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span></dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Academic standing</dt>
                <dd><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $student->academicStandingBadgeClass() }}">{{ $student->academicStandingLabel() }}</span></dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Scholarship</dt>
                <dd class="font-semibold text-gray-900">{{ $student->scholarshipLabel() }}</dd>
            </div>
            <div>
                <dt class="ui-kpi-label mb-1">Tuition</dt>
                <dd class="font-semibold text-gray-900">{{ $student->tuitionLabel() }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="ui-kpi-label mb-1">Subjects · Units</dt>
                <dd class="font-semibold text-gray-900 tabular-nums">{{ $student->subjects_count }} subjects · {{ number_format($student->units_enrolled, 1) }} units</dd>
            </div>
        </dl>
    </section>
</div>

{{-- At-risk --}}
<div class="ui-panel overflow-hidden" style="border-left: 4px solid {{ $riskTone['bar'] }};">
    <div class="px-5 py-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between" style="background: linear-gradient(90deg, color-mix(in srgb, {{ $riskTone['bar'] }} 10%, var(--theme-panel)), var(--theme-panel));">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em]" style="color: {{ $riskTone['bar'] }};">At-risk analysis</p>
            <p class="text-sm text-gray-700 leading-relaxed mt-2 max-w-3xl">{{ $analysis['narrative'] }}</p>
        </div>
        <a href="{{ route('at-risk.show', $student) }}" class="ui-btn-filter shrink-0 self-start">Full report</a>
    </div>
</div>
@endsection
