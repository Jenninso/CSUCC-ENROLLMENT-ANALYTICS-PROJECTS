@extends('layouts.app')
@section('title', 'At-risk report '.$student->student_id)
@section('hidePageHead', '1')

@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('at-risk.index') }}" class="ui-link text-sm">← Back to at-risk students</a>
        <h3 class="ui-page-title mt-2 font-mono">{{ $student->student_id }}</h3>
        <p class="ui-page-sub">{{ $analysis['program'] }} · {{ $analysis['year_label'] }} {{ $analysis['section'] }} · {{ $analysis['term'] }}</p>
    </div>
    <a href="{{ route('at-risk.print', $student) }}" target="_blank" class="ui-btn-secondary shrink-0">Print report</a>
</div>

@include('at-risk.partials.report')

<div class="ui-panel p-5 mb-4">
    <h3 class="text-base font-semibold text-gray-800 mb-3">Interventions and follow-up</h3>
    @forelse($interventions as $intervention)
        <div class="border-b border-gray-100 last:border-0 py-3">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $intervention->typeLabel() }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $intervention->provided_on?->format('M j, Y') }} · GPA then {{ number_format((float) $intervention->gpa_at_record, 2) }} · attendance {{ number_format((float) $intervention->attendance_at_record, 1) }}%</p>
                    @if($intervention->notes)
                        <p class="text-sm text-gray-700 mt-1">{{ $intervention->notes }}</p>
                    @endif
                </div>
                @if(auth()->user()?->canAccess('at_risk.manage'))
                <form method="POST" action="{{ route('at-risk.interventions.update', $intervention) }}" class="flex items-center gap-2">
                    @csrf @method('PUT')
                    <select name="outcome" class="ui-input text-sm">
                        @foreach(\App\Models\StudentIntervention::OUTCOMES as $value => $label)
                            <option value="{{ $value }}" @selected($intervention->outcome === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="ui-btn-secondary">Save</button>
                </form>
                @else
                    <span class="text-xs font-medium text-gray-600">{{ $intervention->outcomeLabel() }}</span>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">No intervention has been recorded for this student.</p>
    @endforelse

    @if(auth()->user()?->canAccess('at_risk.manage'))
    <form method="POST" action="{{ route('at-risk.interventions.store', $student) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 pt-4 border-t border-gray-100">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="intervention_type">Support provided</label>
            <select id="intervention_type" name="intervention_type" class="ui-input" required>
                @foreach(\App\Models\StudentIntervention::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('intervention_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="provided_on">Date</label>
            <input id="provided_on" type="date" name="provided_on" value="{{ old('provided_on', now()->toDateString()) }}" class="ui-input" required>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="ui-input" maxlength="2000">{{ old('notes') }}</textarea>
        </div>
        <div>
            <button type="submit" class="ui-btn-primary">Record intervention</button>
        </div>
    </form>
    @endif
</div>
@endsection
