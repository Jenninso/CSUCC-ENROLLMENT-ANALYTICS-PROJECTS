@extends('layouts.app')
@section('title', isset($academicYear) ? 'Edit Academic Year' : 'Add Academic Year')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($academicYear) ? route('academic-years.update', $academicYear) : route('academic-years.store') }}" class="space-y-4">@csrf @if(isset($academicYear)) @method('PUT') @endif
<div><label class="block text-sm mb-1">Academic Year</label><input name="academic_year" value="{{ old('academic_year', $academicYear->academic_year ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', isset($academicYear) ? $academicYear->start_date->format('Y-m-d') : '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">End Date</label><input type="date" name="end_date" value="{{ old('end_date', isset($academicYear) ? $academicYear->end_date->format('Y-m-d') : '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="active" @selected(old('status', $academicYear->status ?? '') == 'active')>Active</option><option value="inactive" @selected(old('status', $academicYear->status ?? '') == 'inactive')>Inactive</option></select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('academic-years.index') }}" class="px-4 py-2">Cancel</a>
</form></div>
@endsection
