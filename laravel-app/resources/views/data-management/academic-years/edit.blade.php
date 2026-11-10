@extends('layouts.app')
@section('title', 'Edit Academic Year')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('data-management.academic-years.update', $academicYear) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year Name</label>
                <input type="text" name="academic_year" value="{{ old('academic_year', $academicYear->academic_year) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('academic_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($academicYear->start_date)->format('Y-m-d') ?? $academicYear->start_date) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date', optional($academicYear->end_date)->format('Y-m-d') ?? $academicYear->end_date) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <select name="status" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="active" @selected(old('status', $academicYear->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $academicYear->status) === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Update</button>
                <a href="{{ route('data-management.academic-years.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
