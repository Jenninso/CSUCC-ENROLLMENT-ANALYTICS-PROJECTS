@extends('layouts.app')
@section('title', 'Enrollment Trends')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-5 mb-6"><h3 class="font-semibold mb-4">Overall Enrollment Trend</h3><canvas id="trendChart" height="100"></canvas></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-left">Total Enrollment</th></tr></thead>
        <tbody>@foreach($trends as $t)<tr class="border-t"><td class="px-4 py-3">{{ $t->academic_year }} {{ $t->semester_name }}</td><td class="px-4 py-3">{{ number_format($t->total) }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')
<script>new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: @json($trends->map(fn($t) => $t->academic_year.' '.$t->semester_name)), datasets: [{ label: 'Enrollment', data: @json($trends->pluck('total')), borderColor: '#6366f1', tension: 0.3, fill: true, backgroundColor: 'rgba(99,102,241,0.1)' }] } });</script>
@endpush
