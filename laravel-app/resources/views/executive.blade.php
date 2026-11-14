@extends('layouts.app')
@section('title', 'Executive Dashboard')
@section('subtitle', 'Simplified view for decision makers. Color is not the only indicator; each card also includes status text.')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $growthStatus = ($growthRate ?? 0) >= 0 ? ['Positive', 'bg-emerald-50 border-emerald-200 text-emerald-800'] : ['Needs attention', 'bg-amber-50 border-amber-200 text-amber-800'];
        $retStatus = ($retentionRate ?? 0) >= 80 ? ['Positive', 'bg-emerald-50 border-emerald-200 text-emerald-800'] : ['Needs attention', 'bg-amber-50 border-amber-200 text-amber-800'];
    @endphp
    <div class="rounded-lg border p-5 {{ $growthStatus[1] }}">
        <p class="text-sm">Current enrollment</p>
        <p class="text-3xl font-bold mt-1">{{ number_format($totalEnrollment ?? 0) }}</p>
        <p class="text-xs mt-2">Status: observed headcount</p>
    </div>
    <div class="rounded-lg border p-5 {{ $growthStatus[1] }}">
        <p class="text-sm">Enrollment growth</p>
        <p class="text-3xl font-bold mt-1">{{ $growthRate ?? 0 }}%</p>
        <p class="text-xs mt-2">Status: {{ $growthStatus[0] }}</p>
    </div>
    <div class="rounded-lg border p-5 {{ $retStatus[1] }}">
        <p class="text-sm">Retention</p>
        <p class="text-3xl font-bold mt-1">{{ $retentionRate ?? 0 }}%</p>
        <p class="text-xs mt-2">Status: {{ $retStatus[0] }}</p>
    </div>
    <div class="rounded-lg border p-5 bg-white">
        <p class="text-sm text-gray-500">Forecast</p>
        <p class="text-3xl font-bold mt-1 text-gray-800">{{ $forecast ? number_format($forecast->predicted_enrollment) : '—' }}</p>
        <p class="text-xs mt-2 text-gray-500">Model estimate, not an observed count</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold mb-3">Top programs</h3>
        <ul class="text-sm space-y-2">
            @foreach(($topPrograms ?? collect())->take(5) as $program)
                <li class="flex justify-between"><span>{{ $program['program_name'] }}</span><span>{{ number_format($program['current']) }}</span></li>
            @endforeach
        </ul>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold mb-3">Declining programs</h3>
        <ul class="text-sm space-y-2">
            @forelse($decliningPrograms ?? [] as $program)
                <li class="flex justify-between"><span>{{ $program['program_name'] }}</span><span class="text-red-700">{{ $program['growth_rate'] }}%</span></li>
            @empty
                <li class="text-gray-400">No declining programs in this period.</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold mb-3">Alerts</h3>
        @forelse($alerts ?? [] as $alert)
            <p class="text-sm border-b py-2">{{ $alert->title }} — {{ $alert->severity }}</p>
        @empty
            <p class="text-sm text-gray-400">No unread alerts.</p>
        @endforelse
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold mb-3">AI recommendations</h3>
        @forelse($insights ?? [] as $insight)
            <p class="text-sm border-b py-2">{{ $insight->recommendation }}</p>
        @empty
            <p class="text-sm text-gray-400">Generate insights from AI Decision Support.</p>
        @endforelse
    </div>
</div>
@endsection
