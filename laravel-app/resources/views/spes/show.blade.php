@extends('layouts.app')
@section('title', $application->reference_no)

@section('content')
@php $studentMode = ! empty($studentMode); @endphp
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <a href="{{ $studentMode ? route('student.spes.index') : route('spes.index') }}" class="ui-link text-sm">← Back to SPR</a>
        <h3 class="ui-page-title mt-2">{{ $application->fullName() }}</h3>
        <p class="ui-page-sub font-mono">{{ $application->reference_no }} · Student Personal Record</p>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <a href="{{ $studentMode ? route('student.spes.print', $application) : route('spes.print', $application) }}" target="_blank" class="ui-btn-secondary">Print form</a>
        <a href="{{ $studentMode ? route('student.spes.pdf', $application) : route('spes.pdf', $application) }}" class="ui-btn-primary">Download PDF</a>
        @if($studentMode)
            @if($application->studentMayEdit())
            <a href="{{ route('student.spes.edit', $application) }}" class="ui-btn-primary">Edit</a>
            @endif
            @if($application->status === 'submitted')
            <form method="POST" action="{{ route('student.spes.destroy', $application) }}" onsubmit="return confirm('Withdraw this record?')">
                @csrf @method('DELETE')
                <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Withdraw</button>
            </form>
            @endif
        @elseif(auth()->user()?->canAccess('enrollment.manage'))
        <form method="POST" action="{{ route('spes.destroy', $application) }}" onsubmit="return confirm('Delete {{ $application->reference_no }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Delete</button>
        </form>
        @endif
    </div>
</div>

@unless($studentMode)
<div class="mb-4 max-w-[8.5in] rounded-lg border px-4 py-3 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {{ $application->student_can_edit ? 'border-emerald-300 bg-emerald-50 text-emerald-950' : 'border-amber-300 bg-amber-50 text-amber-950' }}">
    @if(! $application->user_id)
        <p>This record is not linked to a student account. Staff cannot type on the form.</p>
    @elseif($application->student_can_edit)
        <p>The student can edit this record once. It locks again after they save. Staff cannot change the answers.</p>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <form method="POST" action="{{ route('spes.student-access', $application) }}">
            @csrf
            <input type="hidden" name="allow" value="0">
            <button type="submit" class="ui-btn-secondary">Lock student editing</button>
        </form>
        @endif
    @else
        <p>This SPR is locked. Only the student can edit it, and only after you allow one edit.</p>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <form method="POST" action="{{ route('spes.student-access', $application) }}">
            @csrf
            <input type="hidden" name="allow" value="1">
            <button type="submit" class="ui-btn-primary">Allow one edit</button>
        </form>
        @endif
    @endif
</div>
@if(auth()->user()?->canAccess('enrollment.manage'))
<form method="POST" action="{{ route('spes.status', $application) }}" class="mb-4 max-w-[8.5in] ui-panel p-4 flex flex-wrap items-end gap-3">
    @csrf @method('PUT')
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Office status</label>
        <select name="status" class="ui-input">
            @foreach(\App\Models\SpesApplication::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($application->status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="grow min-w-[16rem] flex-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Office remarks</label>
        <input type="text" name="remarks" value="{{ $application->remarks }}" maxlength="2000" class="ui-input">
    </div>
    <button type="submit" class="ui-btn-secondary">Save status</button>
</form>
@endif
@endunless

@if($studentMode && ! $application->student_can_edit)
<div class="mb-4 max-w-[8.5in] rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
    This record is locked. You can edit it only after the office allows one edit.
</div>
@elseif($studentMode && $application->student_can_edit)
<div class="mb-4 max-w-[8.5in] rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
    The office allowed one more edit. After you save, the record locks again.
</div>
@endif

@include('spes.paper', ['editing' => false])
@endsection
