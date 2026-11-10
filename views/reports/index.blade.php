@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ route('reports.store') }}" class="space-y-4">@csrf
<div><label class="block text-sm mb-1">Report Type</label><select name="report_type" required class="w-full border rounded px-3 py-2"><option value="enrollment">Enrollment Report</option><option value="programs">Program Analysis</option><option value="retention">Retention Report</option></select></div>
<div><label class="block text-sm mb-1">Academic Year (optional)</label><select name="academic_year_id" class="w-full border rounded px-3 py-2"><option value="">All Years</option>@foreach($academicYears as $y)<option value="{{ $y->id }}">{{ $y->academic_year }}</option>@endforeach</select></div>
<div><label class="block text-sm mb-1">Format</label><select name="format" required class="w-full border rounded px-3 py-2"><option value="csv">CSV</option><option value="excel">Excel (coming soon)</option></select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Generate Report</button>
</form></div>
@endsection
