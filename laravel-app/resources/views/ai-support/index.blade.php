@extends('layouts.app')
@section('title', 'AI Decision Support')

@section('content')
<div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-4 mb-6 text-sm">
    This module provides decision support, not automatic decisions. Insights are generated from verified analytics. Speculation is labeled as interpretation. Administrators must review every recommendation.
</div>

<div class="flex gap-3 mb-6">
    <form method="POST" action="{{ route('ai-support.generate') }}">
        @csrf
        <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Generate insights from current data</button>
    </form>
    <a href="{{ route('ai-support.what-if') }}" class="px-4 py-2 bg-gray-100 text-sm rounded-lg">What-if analysis</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @forelse($insights as $insight)
    <a href="{{ route('ai-support.show', $insight) }}" class="block bg-white rounded-lg shadow-sm p-5 hover:ring-2 hover:ring-indigo-200">
        <div class="flex justify-between gap-2 mb-2">
            <span class="text-xs uppercase tracking-wide text-indigo-600">{{ str_replace('_', ' ', $insight->insight_type) }}</span>
            <span class="text-xs font-medium">{{ $insight->severity }} · {{ $insight->status }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">{{ $insight->title }}</h3>
        <p class="text-sm text-gray-600 mt-2">{{ \Illuminate\Support\Str::limit($insight->recommendation, 160) }}</p>
        <p class="text-xs text-gray-400 mt-3">Confidence {{ $insight->confidence ?? '—' }}%</p>
    </a>
    @empty
    <div class="col-span-2 bg-white rounded-lg p-8 text-center text-gray-400">No insights yet. Generate them after enrollment data is loaded.</div>
    @endforelse
</div>
<div class="mt-4">{{ $insights->links() }}</div>
@endsection
