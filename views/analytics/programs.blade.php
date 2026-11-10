@extends('layouts.app')
@section('title', 'Program Analytics')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Program Comparison</h3><canvas id="progChart"></canvas></div>
    <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-4">Program Share</h3><canvas id="shareChart"></canvas></div>
</div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Program</th><th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Enrollment</th><th class="px-4 py-3 text-left">Share</th></tr></thead>
        <tbody>@foreach($programs as $p)<tr class="border-t"><td class="px-4 py-3">{{ $p->program_name }}</td><td class="px-4 py-3">{{ $p->program_code }}</td><td class="px-4 py-3">{{ number_format($p->total) }}</td><td class="px-4 py-3">{{ $total > 0 ? round(($p->total/$total)*100,1) : 0 }}%</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')
<script>
new Chart(document.getElementById('progChart'), { type: 'bar', data: { labels: @json($programs->take(10)->pluck('program_name')), datasets: [{ data: @json($programs->take(10)->pluck('total')), backgroundColor: '#6366f1' }] }, options: { indexAxis: 'y' } });
new Chart(document.getElementById('shareChart'), { type: 'pie', data: { labels: @json($programs->take(8)->pluck('program_name')), datasets: [{ data: @json($programs->take(8)->pluck('total')), backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#0ea5e9','#8b5cf6','#ec4899','#14b8a6'] }] } });
</script>
@endpush
