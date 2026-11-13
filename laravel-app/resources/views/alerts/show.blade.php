@extends('layouts.app')
@section('title', 'Alert details')

@section('content')
@php
    $severity = $alert->severity ?? 'info';
    $badges = [
        'critical' => 'bg-red-100 text-red-700',
        'high' => 'bg-orange-100 text-orange-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'medium' => 'bg-amber-100 text-amber-700',
        'info' => 'bg-sky-100 text-sky-700',
    ];
@endphp
<div class="max-w-3xl space-y-4">
    <a href="{{ route('alerts.index') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">Back to alerts</a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-2">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium uppercase {{ $badges[$severity] ?? $badges['info'] }}">{{ $severity }}</span>
                <h2 class="text-xl font-bold text-gray-900">{{ $alert->title }}</h2>
                <p class="text-xs text-gray-400">{{ $alert->created_at?->format('M j, Y g:i A') }} · {{ $alert->created_at?->diffForHumans() }}</p>
            </div>
            @unless($alert->is_read)
            <form method="POST" action="{{ route('alerts.mark-read', $alert) }}">
                @csrf
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700">Mark as read</button>
            </form>
            @endunless
        </div>

        <p class="text-sm text-gray-700 leading-relaxed">{{ $alert->explanation }}</p>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-400">Program</dt>
                <dd class="font-medium text-gray-800">{{ $alert->program?->program_name ?? 'Institution' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-400">Metric</dt>
                <dd class="font-medium text-gray-800">{{ str_replace('_', ' ', (string) $alert->metric) }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-400">Current value</dt>
                <dd class="font-medium text-gray-800">{{ $alert->current_value }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-400">Reference value</dt>
                <dd class="font-medium text-gray-800">{{ $alert->expected_value }}</dd>
            </div>
        </dl>

        @if($alert->recommended_action)
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Recommended action</p>
            <p class="text-sm text-gray-800 mt-1">{{ $alert->recommended_action }}</p>
        </div>
        @endif

        @if($student && auth()->user()?->canAccess('enrollment.view'))
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('at-risk.show', $student) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Open at-risk report</a>
            <a href="{{ route('students.show', $student) }}" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg">Open student record</a>
        </div>
        @endif
    </div>
</div>
@endsection
