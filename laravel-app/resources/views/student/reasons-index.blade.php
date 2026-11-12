@extends('layouts.app')
@section('title', 'Status reasons')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">Status reasons</h3>
        <p class="ui-page-sub">Tell the office why you shifted program, transferred, withdrew a subject, dropped a subject, or stopped for the term.</p>
    </div>
    <a href="{{ route('student.reasons.create') }}" class="ui-btn-primary shrink-0">Add reason</a>
</div>

<div class="space-y-3">
    @forelse($reasons as $reason)
    <article class="ui-panel p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h4 class="font-semibold text-gray-900">{{ $reason->actionLabel() }}</h4>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $reason->effective_date?->format('F j, Y') ?? 'No effective date' }}
                    · <span class="font-medium">{{ $reason->statusLabel() }}</span>
                </p>
            </div>
            @if($reason->isOpen())
            <div class="flex gap-3 text-sm">
                <a href="{{ route('student.reasons.edit', $reason) }}" class="ui-link">Edit</a>
                <form method="POST" action="{{ route('student.reasons.destroy', $reason) }}" onsubmit="return confirm('Remove this reason?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-700 font-semibold">Remove</button>
                </form>
            </div>
            @endif
        </div>
        <p class="mt-3 text-sm text-gray-800 whitespace-pre-line">{{ $reason->reason }}</p>
        <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600">
            @if($reason->subject_name)<div>Subject: {{ $reason->subject_name }}</div>@endif
            @if($reason->from_program)<div>From: {{ $reason->from_program }}</div>@endif
            @if($reason->to_program)<div>To: {{ $reason->to_program }}</div>@endif
            @if($reason->previous_school)<div>Previous school: {{ $reason->previous_school }}</div>@endif
        </dl>
        @if($reason->staff_remarks)
        <p class="mt-3 text-sm text-emerald-900 bg-emerald-50 rounded-lg px-3 py-2">Office note: {{ $reason->staff_remarks }}</p>
        @endif
    </article>
    @empty
    <div class="ui-panel p-8 text-center text-sm text-gray-500">No reasons yet.</div>
    @endforelse
</div>
<div class="mt-4">{{ $reasons->links() }}</div>
@endsection
