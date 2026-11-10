@extends('layouts.app')
@section('title', $alert->title)

@section('content')
<div class="max-w-2xl bg-white rounded-lg shadow-sm p-6 space-y-3">
    <p class="text-xs uppercase">{{ $alert->severity }}</p>
    <h2 class="text-xl font-bold">{{ $alert->title }}</h2>
    <p><strong>Program:</strong> {{ $alert->program?->program_name ?? 'Institution' }}</p>
    <p><strong>Metric:</strong> {{ $alert->metric }}</p>
    <p><strong>Current value:</strong> {{ $alert->current_value }}</p>
    <p><strong>Reference value:</strong> {{ $alert->expected_value }}</p>
    <p>{{ $alert->explanation }}</p>
    <p class="text-sm text-gray-600"><strong>Recommended action:</strong> {{ $alert->recommended_action }}</p>
    @unless($alert->is_read)
    <form method="POST" action="{{ route('alerts.mark-read', $alert) }}">@csrf<button class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Mark as read</button></form>
    @endunless
</div>
@endsection
