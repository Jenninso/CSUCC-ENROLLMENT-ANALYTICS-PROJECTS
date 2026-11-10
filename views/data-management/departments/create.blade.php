@extends('layouts.app')
@section('title', isset($department) ? 'Edit Department' : 'Add Department')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($department) ? route('departments.update', $department) : route('departments.store') }}" class="space-y-4">@csrf @if(isset($department)) @method('PUT') @endif
<div><label class="block text-sm mb-1">Department Name</label><input name="department_name" value="{{ old('department_name', $department->department_name ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Department Code</label><input name="department_code" value="{{ old('department_code', $department->department_code ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">College</label><select name="college_id" required class="w-full border rounded px-3 py-2">@foreach($colleges as $c)<option value="{{ $c->id }}" @selected(old('college_id', $department->college_id ?? '') == $c->id)>{{ $c->college_name }}</option>@endforeach</select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('departments.index') }}">Cancel</a>
</form></div>
@endsection
