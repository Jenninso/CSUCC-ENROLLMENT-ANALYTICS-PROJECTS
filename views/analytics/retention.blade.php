@extends('layouts.app')
@section('title', 'Retention Analytics')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-5 mb-6"><h3 class="font-semibold mb-4">Retention by Program</h3><canvas id="retentionChart"></canvas></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Program</th><th class="px-4 py-3 text-left">Total</th><th class="px-4 py-3 text-left">Retained</th><th class="px-4 py-3 text-left">Retention Rate</th></tr></thead>
        <tbody>@foreach($retentionByProgram as $p)<tr class="border-t"><td class="px-4 py-3">{{ $p->program_name }}</td><td class="px-4 py-3">{{ number_format($p->total) }}</td><td class="px-4 py-3">{{ number_format($p->retained) }}</td><td class="px-4 py-3">{{ $p->retention_rate }}%</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')
<script>new Chart(document.getElementById('retentionChart'), { type: 'bar', data: { labels: @json($retentionByProgram->take(10)->pluck('program_name')), datasets: [{ label: 'Retention %', data: @json($retentionByProgram->take(10)->pluck('retention_rate')), backgroundColor: '#10b981' }] } });</script>
@endpush
