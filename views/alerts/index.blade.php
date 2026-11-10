@extends('layouts.app')
@section('title', 'Alerts')
@section('content')
<form method="GET" class="mb-4"><select name="severity" onchange="this.form.submit()" class="border rounded px-3 py-2 text-sm"><option value="">All Severities</option>@foreach(['low','medium','high','critical'] as $s)<option value="{{ $s }}" @selected(request('severity')==$s)>{{ ucfirst($s) }}</option>@endforeach</select></form>
<div class="space-y-3">@forelse($alerts as $alert)
<div class="bg-white rounded-lg shadow-sm p-4 border-l-4 {{ $alert->severity === 'critical' ? 'border-red-500' : ($alert->severity === 'high' ? 'border-orange-500' : ($alert->severity === 'medium' ? 'border-amber-500' : 'border-blue-500')) }}">
<div class="flex justify-between items-start"><div><h3 class="font-semibold">{{ $alert->title }}</h3><p class="text-sm text-gray-500 mt-1">{{ $alert->explanation }}</p><p class="text-xs text-gray-400 mt-2">{{ $alert->metric }}: {{ $alert->current_value }} @if($alert->expected_value)(expected: {{ $alert->expected_value }})@endif</p></div>
@if(!$alert->is_read)<form action="{{ route('alerts.read', $alert) }}" method="POST">@csrf<button class="text-sm text-indigo-600">Mark Read</button></form>@endif</div></div>
@empty<p class="text-gray-400">No alerts found.</p>@endforelse</div>
<div class="mt-4">{{ $alerts->links() }}</div>
@endsection
