@extends('layouts.app')
@section('title', 'What-if analysis')

@section('content')
<div class="bg-sky-50 border border-sky-200 text-sky-900 rounded-lg p-4 mb-6 text-sm">
    Outputs on this page are simulations based on your assumptions. They are not statistical forecasts and must not be treated as predicted enrollment.
</div>

<form method="POST" class="bg-white rounded-lg shadow-sm p-6 max-w-xl space-y-4 mb-6">
    @csrf
    <p class="text-sm text-gray-600">Current enrollment: <strong>{{ number_format($data['totalEnrollment']) }}</strong> · New: {{ number_format($data['newStudents']) }} · Returning/continuing: {{ number_format($data['returningStudents']) }}</p>
    <label class="block text-sm">Recruitment improvement (%)
        <input type="number" step="0.1" name="recruitment_pct" value="{{ $defaults['recruitment_pct'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Retention improvement (%)
        <input type="number" step="0.1" name="retention_pct" value="{{ $defaults['retention_pct'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Planning capacity
        <input type="number" name="capacity" value="{{ $defaults['capacity'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Run simulation</button>
</form>

@if($result)
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach(['current','recruitment','retention'] as $key)
    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">{{ $result[$key]['label'] }}</p>
        <p class="text-2xl font-bold mt-1">{{ number_format($result[$key]['total']) }}</p>
        <p class="text-xs text-gray-500 mt-2">{{ $result[$key]['note'] }}</p>
    </div>
    @endforeach
</div>
<div class="bg-white rounded-lg shadow-sm p-5 mt-4">
    <p class="font-semibold">{{ $result['combined']['label'] }}: {{ number_format($result['combined']['total']) }}</p>
    <p class="text-sm mt-1">Capacity cap {{ number_format($result['combined']['capacity']) }} → simulated outcome {{ number_format($result['combined']['capped']) }}</p>
    @if($result['combined']['exceeds_capacity'])
        <p class="text-sm text-red-700 mt-2">The combined simulation exceeds planning capacity.</p>
    @endif
    <p class="text-xs text-gray-500 mt-3">{{ $result['disclaimer'] }}</p>
</div>
@endif
@endsection
