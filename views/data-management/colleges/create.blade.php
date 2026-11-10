@extends('layouts.app')
@section('title', isset($college) ? 'Edit College' : 'Add College')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($college) ? route('colleges.update', $college) : route('colleges.store') }}" class="space-y-4">@csrf @if(isset($college)) @method('PUT') @endif
<div><label class="block text-sm mb-1">College Name</label><input name="college_name" value="{{ old('college_name', $college->college_name ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">College Code</label><input name="college_code" value="{{ old('college_code', $college->college_code ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('colleges.index') }}">Cancel</a>
</form></div>
@endsection
