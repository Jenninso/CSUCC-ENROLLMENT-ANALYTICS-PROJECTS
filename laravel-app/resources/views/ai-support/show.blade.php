@extends('layouts.app')
@section('title', 'Insight detail')

@section('content')
<div class="max-w-3xl bg-white rounded-lg shadow-sm p-6 space-y-4">
    <p class="text-xs uppercase text-indigo-600">{{ str_replace('_', ' ', $insight->insight_type) }} · {{ $insight->severity }}</p>
    <h2 class="text-xl font-bold">{{ $insight->title }}</h2>
    <div>
        <h3 class="text-sm font-semibold text-gray-500">Situation</h3>
        <p>{{ $insight->situation }}</p>
    </div>
    <div class="bg-slate-50 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-gray-500">Evidence (fact / forecast)</h3>
        <p>{{ $insight->evidence }}</p>
    </div>
    <div>
        <h3 class="text-sm font-semibold text-gray-500">Possible interpretation</h3>
        <p>{{ $insight->interpretation }}</p>
    </div>
    @if($insight->forecast_note)
    <div>
        <h3 class="text-sm font-semibold text-gray-500">Forecast</h3>
        <p>{{ $insight->forecast_note }}</p>
    </div>
    @endif
    <div>
        <h3 class="text-sm font-semibold text-gray-500">Recommendation</h3>
        <p>{{ $insight->recommendation }}</p>
    </div>
    <p class="text-sm text-gray-500">Priority: {{ $insight->severity }} · Confidence: {{ $insight->confidence }}% · Status: {{ $insight->status }}</p>

    <form method="POST" action="{{ route('ai-support.review', $insight) }}" class="flex gap-2 pt-2">
        @csrf
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            <option value="pending_review" @selected($insight->status === 'pending_review')>Pending review</option>
            <option value="accepted" @selected($insight->status === 'accepted')>Accepted</option>
            <option value="rejected" @selected($insight->status === 'rejected')>Rejected</option>
        </select>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Save review</button>
        <a href="{{ route('ai-support.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Back</a>
    </form>
</div>
@endsection
