@extends('layouts.app')
@section('title', isset($application) ? 'Edit SPR' : 'New SPR')

@php
    $studentMode = ! empty($studentMode);
    $formAction = isset($application)
        ? ($studentMode ? route('student.spes.update', $application) : route('spes.update', $application))
        : ($studentMode ? route('student.spes.store') : route('spes.store'));
    $cancelUrl = isset($application)
        ? ($studentMode ? route('student.spes.show', $application) : route('spes.show', $application))
        : ($studentMode ? route('student.spes.index') : route('spes.index'));
@endphp

@section('content')
<div class="mb-4 flex items-center justify-between gap-3">
    <div>
        <a href="{{ $cancelUrl }}" class="ui-link text-sm">← Back</a>
        <h3 class="ui-page-title mt-2">Student Personal Record (SPR)</h3>
        <p class="ui-page-sub">One long bond page, 8.5 × 13 inches. Print the saved record to match the paper form.</p>
    </div>
    @isset($application)
    <a href="{{ $studentMode ? route('student.spes.pdf', $application) : route('spes.pdf', $application) }}" class="ui-btn-secondary shrink-0">Download PDF</a>
    @endisset
</div>

@if($studentMode && isset($application))
<div class="mb-4 max-w-[8.5in] rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <p>Double check your Form. The blank lines are open — click a line and type. The office section under “DO NOT WRITE ANYTHING BELOW THIS LINE” stays blank for staff.{{ $application->isLocked() ? ' After you save, this record locks again.' : '' }}</p>
    <button type="submit" form="spr-form" class="ui-btn-primary shrink-0">Save SPR</button>
</div>
@endif

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm max-w-[8.5in]">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form id="spr-form" method="POST" action="{{ $formAction }}">
    @csrf
    @isset($application) @method('PUT') @endisset
    @include('spes.paper', ['editing' => true])
    <div class="max-w-[8.5in] mx-auto mt-4 ui-panel p-4 flex flex-wrap items-end gap-3">
        @unless($studentMode)
        @if(isset($application) && $application->user_id)
        <label class="flex items-start gap-2 text-sm text-gray-800 sm:col-span-2 w-full">
            <input type="checkbox" name="allow_student_edit" value="1" class="mt-0.5 rounded border-gray-300" @checked(old('allow_student_edit', $application->student_can_edit))>
            <span>Allow the student to edit this record. After they save an approved record, it locks again.</span>
        </label>
        @endif
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Office status</label>
            <select name="status" class="ui-input">
                @foreach(\App\Models\SpesApplication::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $application->status ?? 'submitted') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grow min-w-[16rem] flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Office remarks</label>
            <input type="text" name="remarks" value="{{ old('remarks', $application->remarks ?? '') }}" class="ui-input">
        </div>
        @else
        <input type="hidden" name="status" value="submitted">
        @endunless
        <button type="submit" class="ui-btn-primary">Save SPR</button>
    </div>
</form>
@endsection
