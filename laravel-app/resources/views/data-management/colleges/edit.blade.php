@extends('layouts.app')
@section('title', 'Edit College')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('data-management.colleges.update', $college) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">College Code</label>
                <input type="text" name="college_code" value="{{ old('college_code', $college->college_code ?? $college->code) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('college_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">College Name</label>
                <input type="text" name="college_name" value="{{ old('college_name', $college->college_name) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('college_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dean / Head</label>
                <input type="text" name="dean_name" value="{{ old('dean_name', $college->dean_name ?? $college->head) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('dean_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Update</button>
                <a href="{{ route('data-management.colleges.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
