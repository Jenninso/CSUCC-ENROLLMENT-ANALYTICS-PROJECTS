@extends('layouts.app')
@section('title', 'Alerts')
@section('hidePageHead', '1')

@section('content')
<div class="page-head">
    <div>
        <p class="page-kicker">CSUCC · CEIT</p>
        <h3 class="ui-page-title">Alerts</h3>
        <p class="ui-page-sub">Enrollment alerts and notifications for staff review.</p>
    </div>
    <form method="POST" action="{{ route('alerts.generate') }}" class="shrink-0">
        @csrf
        <button type="submit" class="ui-btn-filter">Evaluate alerts</button>
    </form>
</div>

<div class="ui-panel p-4 mb-4 sticky top-2 z-20" style="backdrop-filter: blur(10px); background: color-mix(in srgb, var(--theme-panel) 94%, transparent);">
    <form method="GET" action="{{ route('alerts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <label class="sm:col-span-1 lg:col-span-2">
            <span class="block text-xs font-semibold text-gray-500 mb-1">Severity</span>
            <select name="severity" class="ui-input">
                <option value="">All Severities</option>
                <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="ui-btn-filter">Filter</button>
            <a href="{{ route('alerts.index') }}" class="ui-btn-clear">Clear</a>
        </div>
    </form>
    @if(request('severity'))
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Active filters</span>
        <a href="{{ route('alerts.index') }}" class="ui-chip ui-chip-info hover:opacity-80">
            {{ ucfirst(request('severity')) }} <span class="font-bold">×</span>
        </a>
        <a href="{{ route('alerts.index') }}" class="text-xs font-semibold text-amber-800 underline">Clear all</a>
    </div>
    @endif
</div>

<div class="ui-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Alert</th>
                    <th class="hidden md:table-cell">When</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts ?? [] as $alert)
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
                <tr class="{{ ($alert->is_read ?? false) ? 'opacity-70' : '' }}">
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase {{ $badges[$severity] ?? $badges['info'] }}">{{ $severity }}</span>
                    </td>
                    <td class="max-w-xl">
                        <a href="{{ route('alerts.show', $alert) }}" class="font-semibold ui-link">{{ $alert->title }}</a>
                        <p class="text-xs text-gray-600 mt-0.5">{{ \Illuminate\Support\Str::limit($alert->explanation ?: ($alert->message ?? ''), 120) }}</p>
                    </td>
                    <td class="hidden md:table-cell text-xs text-gray-500 whitespace-nowrap">{{ $alert->created_at?->diffForHumans() }}</td>
                    <td class="text-center">
                        @if(!($alert->is_read ?? false))
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-800">
                                <span class="w-2 h-2 rounded-full bg-teal-500"></span> Unread
                            </span>
                        @else
                            <span class="text-xs text-gray-500">Read</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap">
                        <a href="{{ route('alerts.show', $alert) }}" class="ui-link text-xs font-bold">Open</a>
                        @if(!($alert->is_read ?? false))
                        <form method="POST" action="{{ route('alerts.mark-read', $alert) }}" class="inline ml-2">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-gray-600 hover:text-gray-900 underline">Mark read</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center">
                        <p class="text-base font-bold text-gray-800 mb-1">No alerts found</p>
                        <p class="text-sm text-gray-500 mb-4">Run Evaluate alerts to scan the current CEIT file, or clear the severity filter.</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <form method="POST" action="{{ route('alerts.generate') }}">
                                @csrf
                                <button type="submit" class="ui-btn-filter">Evaluate alerts</button>
                            </form>
                            <a href="{{ route('alerts.index') }}" class="ui-btn-clear">Clear filters</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($alerts) && method_exists($alerts, 'links') && $alerts->hasPages())
    <div class="px-4 py-3 border-t">{{ $alerts->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
