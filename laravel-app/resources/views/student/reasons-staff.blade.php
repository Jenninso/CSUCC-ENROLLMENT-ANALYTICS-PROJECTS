@extends('layouts.app')
@section('title', 'Student reasons')

@section('content')
<div class="mb-6">
    <h3 class="ui-page-title">Student reasons</h3>
    <p class="ui-page-sub">Reasons students submitted for a program shift, transfer, subject withdrawal, dropped subject, or a stopped term.</p>
</div>

<div class="ui-panel p-4 mb-4">
    <form method="GET" action="{{ route('student-reasons.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Student ID, subject, or reason" class="ui-input sm:col-span-2">
        <select name="action_type" class="ui-input">
            <option value="">All actions</option>
            @foreach(\App\Models\StudentStatusReason::ACTIONS as $key => $label)
                <option value="{{ $key }}" {{ request('action_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="ui-input">
            <option value="">All statuses</option>
            @foreach(\App\Models\StudentStatusReason::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-4">
            <button class="ui-btn-primary" type="submit">Filter</button>
            <a href="{{ route('student-reasons.index') }}" class="ui-btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="space-y-4">
    @forelse($reasons as $reason)
    <article class="ui-panel p-5">
        <div class="flex flex-wrap justify-between gap-2">
            <div>
                <p class="font-mono text-xs text-gray-500">{{ $reason->student_id }}</p>
                <h4 class="font-semibold text-gray-900 mt-1">{{ $reason->actionLabel() }}</h4>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $reason->statusLabel() }}</span>
        </div>
        <p class="mt-3 text-sm text-gray-800 whitespace-pre-line">{{ $reason->reason }}</p>
        <p class="mt-2 text-xs text-gray-500">
            {{ $reason->subject_name ? 'Subject: '.$reason->subject_name.' · ' : '' }}
            {{ $reason->from_program ? 'From: '.$reason->from_program.' · ' : '' }}
            {{ $reason->to_program ? 'To: '.$reason->to_program.' · ' : '' }}
            {{ $reason->previous_school ? 'School: '.$reason->previous_school : '' }}
        </p>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <form method="POST" action="{{ route('student-reasons.review', $reason) }}" class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
            @csrf @method('PUT')
            <select name="status" class="ui-input">
                @foreach(\App\Models\StudentStatusReason::STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ $reason->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="staff_remarks" value="{{ $reason->staff_remarks }}" maxlength="2000" placeholder="Office note" class="ui-input sm:col-span-2">
            <button type="submit" class="ui-btn-primary">Save review</button>
        </form>
        @elseif($reason->staff_remarks)
        <p class="mt-3 text-sm text-gray-700">Note: {{ $reason->staff_remarks }}</p>
        @endif
    </article>
    @empty
    <div class="ui-panel p-8 text-center text-sm text-gray-500">No student reasons yet.</div>
    @endforelse
</div>
<div class="mt-4">{{ $reasons->links() }}</div>
@endsection
