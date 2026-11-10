@extends('layouts.app')
@section('title', isset($semester) ? 'Edit Semester' : 'Add Semester')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($semester) ? route('semesters.update', $semester) : route('semesters.store') }}" class="space-y-4">@csrf @if(isset($semester)) @method('PUT') @endif
<div><label class="block text-sm mb-1">Semester Name</label><input name="semester_name" value="{{ old('semester_name', $semester->semester_name ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Academic Year</label><select name="academic_year_id" required class="w-full border rounded px-3 py-2">@foreach($academicYears as $y)<option value="{{ $y->id }}" @selected(old('academic_year_id', $semester->academic_year_id ?? '') == $y->id)>{{ $y->academic_year }}</option>@endforeach</select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('semesters.index') }}">Cancel</a>
</form></div>
@endsection
