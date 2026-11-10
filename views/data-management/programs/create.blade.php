@extends('layouts.app')
@section('title', isset($program) ? 'Edit Program' : 'Add Program')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($program) ? route('programs.update', $program) : route('programs.store') }}" class="space-y-4">@csrf @if(isset($program)) @method('PUT') @endif
<div><label class="block text-sm mb-1">Program Name</label><input name="program_name" value="{{ old('program_name', $program->program_name ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Program Code</label><input name="program_code" value="{{ old('program_code', $program->program_code ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Department</label><select name="department_id" required class="w-full border rounded px-3 py-2">@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $program->department_id ?? '') == $d->id)>{{ $d->department_name }}</option>@endforeach</select></div>
<div><label class="block text-sm mb-1">Degree Level</label><input name="degree_level" value="{{ old('degree_level', $program->degree_level ?? 'Undergraduate') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="active" @selected(old('status', $program->status ?? '') == 'active')>Active</option><option value="inactive" @selected(old('status', $program->status ?? '') == 'inactive')>Inactive</option></select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('programs.index') }}">Cancel</a>
</form></div>
@endsection
