@extends('layouts.app')
@section('title', 'College Analytics')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-5 mb-6"><h3 class="font-semibold mb-4">College Comparison</h3><canvas id="collegeChart"></canvas></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">College</th><th class="px-4 py-3 text-left">Total Enrollment</th></tr></thead>
        <tbody>@foreach($colleges as $c)<tr class="border-t"><td class="px-4 py-3">{{ $c->college_name }}</td><td class="px-4 py-3">{{ number_format($c->total) }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')
<script>new Chart(document.getElementById('collegeChart'), { type: 'bar', data: { labels: @json($colleges->pluck('college_name')), datasets: [{ data: @json($colleges->pluck('total')), backgroundColor: '#6366f1' }] } });</script>
@endpush
