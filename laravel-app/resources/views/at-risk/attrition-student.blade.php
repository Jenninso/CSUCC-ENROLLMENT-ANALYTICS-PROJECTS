@extends('layouts.app')
@section('title', 'Attrition '.$row['student_id'])
@section('hidePageHead', '1')

@section('content')
@php
    $helps = preg_split('/;\s*/', rtrim((string) ($row['suggestion'] ?? ''), '.')) ?: [];
    $helps = array_values(array_filter(array_map(fn ($part) => ucfirst(trim($part)), $helps)));
@endphp

<div class="ui-panel overflow-hidden">
    <div class="px-5 pt-5 pb-4 sm:px-6 border-b border-gray-100" style="background: linear-gradient(180deg, color-mix(in srgb, #0f766e 9%, var(--theme-panel)), var(--theme-panel));">
        <a href="{{ route('at-risk.attrition.show') }}" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-teal-800">Attrition estimate</a>
        <h3 class="ui-page-title mt-1 font-mono">{{ $row['student_id'] }}</h3>
        <p class="ui-page-sub">{{ $row['program'] }} · Year {{ $row['year_level'] }} · {{ $row['chance'] }}% estimate</p>
    </div>
    <div class="p-5 sm:p-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="ui-kpi">
                <p class="ui-kpi-label">GPA</p>
                <p class="ui-kpi-value">{{ number_format($row['gpa'], 2) }}</p>
            </div>
            <div class="ui-kpi" style="border-left-color: #b45309;">
                <p class="ui-kpi-label">Attendance</p>
                <p class="ui-kpi-value">{{ $row['attendance'] }}%</p>
            </div>
            <div class="ui-kpi" style="border-left-color: #b45309;">
                <p class="ui-kpi-label">Failed subjects</p>
                <p class="ui-kpi-value">{{ $row['failed_subjects'] }}</p>
            </div>
            <div class="ui-kpi" style="border-left-color: #b45309;">
                <p class="ui-kpi-label">Estimate</p>
                <p class="ui-kpi-value">{{ $row['chance'] }}%</p>
            </div>
        </div>

        <h3 class="text-base font-semibold mt-6">What to do for this student</h3>
        @if($helps === [])
            <p class="mt-2 text-sm text-gray-500">Run the model again to see a suggestion.</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach($helps as $help)
                    <li class="text-sm text-gray-700 leading-relaxed">{{ $help }}</li>
                @endforeach
            </ul>
        @endif

        @if($record)
            <a href="{{ route('at-risk.show', $record) }}" class="ui-btn-secondary mt-6">Open full at-risk report</a>
        @endif
    </div>
</div>
@endsection
