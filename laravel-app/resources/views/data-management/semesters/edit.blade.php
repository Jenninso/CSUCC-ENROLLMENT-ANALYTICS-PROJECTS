@extends('layouts.app')
@section('title', 'Edit Semester')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('data-management.semesters.update', $semester) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Semester Name</label>
                <input type="text" name="semester_name" value="{{ old('semester_name', $semester->semester_name) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('semester_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                <select name="academic_year_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Select Academic Year</option>
                    @foreach($academicYears ?? [] as $ay)
                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $semester->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->year_name ?? $ay->academic_year }}</option>
                    @endforeach
                </select>
                @error('academic_year_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                <input type="number" name="semester_order" value="{{ old('semester_order', $semester->semester_order ?? $semester->order) }}" min="1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('semester_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $semester->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Set as active semester
            </label>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Update</button>
                <a href="{{ route('data-management.semesters.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
