@extends('layouts.app')
@section('title', 'Data quality')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
    <p class="text-sm text-gray-500">Automated checks on stored enrollment records. Invalid imports are rejected before they reach this table.</p>
    <p class="text-4xl font-bold mt-4">{{ $score }}%</p>
    <p class="text-sm text-gray-500">Data quality score · {{ $total_records }} records · {{ $issue_count }} flagged issues</p>
    <ul class="mt-6 space-y-2 text-sm">
        @forelse($issues as $issue)
            <li class="border rounded-lg p-3">{{ $issue['message'] }}</li>
        @empty
            <li class="text-emerald-700">No data-quality issues detected.</li>
        @endforelse
    </ul>
</div>
@endsection
