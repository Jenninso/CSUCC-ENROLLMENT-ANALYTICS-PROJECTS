@extends('layouts.app')
@section('title', isset($reason) ? 'Edit reason' : 'Add reason')

@php
    $item = $reason ?? null;
    $value = fn (string $field, mixed $default = '') => old($field, $item?->{$field} ?? $default);
@endphp

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('student.reasons.index') }}" class="ui-link text-sm">← Back</a>
    <h3 class="ui-page-title mt-2">{{ isset($reason) ? 'Edit reason' : 'Add a reason' }}</h3>
    <p class="ui-page-sub mb-5">Write what happened in your own words. The office can review it, and you can edit it until they do.</p>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ isset($reason) ? route('student.reasons.update', $reason) : route('student.reasons.store') }}" class="ui-panel p-6 space-y-4">
        @csrf
        @if(isset($reason)) @method('PUT') @endif
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">What happened</label>
            <select name="action_type" required class="ui-input">
                <option value="">Select</option>
                @foreach(\App\Models\StudentStatusReason::ACTIONS as $key => $label)
                    <option value="{{ $key }}" {{ (string) $value('action_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" name="subject_name" value="{{ $value('subject_name') }}" maxlength="150" placeholder="Required for a withdrawn or dropped subject" class="ui-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Effective date</label>
                <input type="date" name="effective_date" value="{{ $value('effective_date') instanceof \Illuminate\Support\Carbon ? $value('effective_date')->toDateString() : $value('effective_date') }}" class="ui-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From program</label>
                <input type="text" name="from_program" value="{{ $value('from_program') }}" maxlength="150" placeholder="Required for a shift or transfer out" class="ui-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To program</label>
                <input type="text" name="to_program" value="{{ $value('to_program') }}" maxlength="150" placeholder="Required for a shift" class="ui-input">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Previous school</label>
                <input type="text" name="previous_school" value="{{ $value('previous_school') }}" maxlength="180" placeholder="Required if you transferred in" class="ui-input">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <textarea name="reason" rows="5" required minlength="20" maxlength="2000" class="ui-input" placeholder="At least 20 characters. Example: I shifted because the schedule conflicted with work, or I withdrew Calculus after missing the first three weeks.">{{ $value('reason') }}</textarea>
        </div>
        <button type="submit" class="ui-btn-primary">Submit reason</button>
    </form>
</div>
@endsection
