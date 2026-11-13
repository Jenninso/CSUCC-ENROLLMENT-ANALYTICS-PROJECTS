@extends('layouts.app')
@section('title', 'Alerts')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <p class="text-sm text-gray-500">Enrollment alerts and notifications.</p>
    <div class="flex flex-wrap gap-2">
    <form method="POST" action="{{ route('alerts.generate') }}">
        @csrf
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Evaluate alerts</button>
    </form>
    <form method="GET" action="{{ route('alerts.index') }}" class="flex gap-2">
        <select name="severity" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Severities</option>
            <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
            <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
            <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
            <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Filter</button>
    </form>
    </div>
</div>

<div class="space-y-3">
    @forelse($alerts ?? [] as $alert)
    @php
        $severity = $alert->severity ?? 'info';
        $colors = [
            'critical' => 'border-red-400 bg-red-50',
            'high' => 'border-orange-400 bg-orange-50',
            'warning' => 'border-amber-400 bg-amber-50',
            'medium' => 'border-amber-400 bg-amber-50',
            'info' => 'border-sky-400 bg-sky-50',
        ];
        $badges = [
            'critical' => 'bg-red-100 text-red-700',
            'high' => 'bg-orange-100 text-orange-700',
            'warning' => 'bg-amber-100 text-amber-700',
            'medium' => 'bg-amber-100 text-amber-700',
            'info' => 'bg-sky-100 text-sky-700',
        ];
    @endphp
    <div class="relative bg-white rounded-lg shadow-sm border-l-4 {{ $colors[$severity] ?? $colors['info'] }} {{ ($alert->is_read ?? false) ? 'opacity-60' : '' }}">
        <a href="{{ route('alerts.show', $alert) }}" class="block p-4 pr-36 hover:bg-black/[0.03] rounded-lg">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium uppercase {{ $badges[$severity] ?? $badges['info'] }}">{{ $severity }}</span>
                @if(!($alert->is_read ?? false))
                    <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                @endif
            </div>
            <h3 class="font-semibold text-gray-800">{{ $alert->title }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($alert->explanation ?: ($alert->message ?? ''), 160) }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $alert->created_at?->diffForHumans() }} · View details</p>
        </a>
        @if(!($alert->is_read ?? false))
        <form method="POST" action="{{ route('alerts.mark-read', $alert) }}" class="absolute top-4 right-4">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg whitespace-nowrap">Mark as read</button>
        </form>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400">No alerts found.</div>
    @endforelse
</div>

@if(isset($alerts) && method_exists($alerts, 'links'))
<div class="mt-4">{{ $alerts->withQueryString()->links() }}</div>
@endif
@endsection
