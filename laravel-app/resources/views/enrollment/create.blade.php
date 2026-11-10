@extends('layouts.app')
@section('title', isset($enrollment) ? 'Edit Enrollment Record' : 'Add Enrollment Record')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ isset($enrollment) ? route('enrollment.update', $enrollment) : route('enrollment.store') }}" class="space-y-5">
            @csrf
            @if(isset($enrollment)) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select name="academic_year_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Academic Year</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay->id }}" {{ old('academic_year_id', $enrollment->academic_year_id ?? '') == $ay->id ? 'selected' : '' }}>{{ $ay->year_name ?? $ay->academic_year }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select name="semester_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Semester</option>
                        @foreach($semesters ?? [] as $sem)
                            <option value="{{ $sem->id }}" {{ old('semester_id', $enrollment->semester_id ?? '') == $sem->id ? 'selected' : '' }}>{{ $sem->semester_name }}</option>
                        @endforeach
                    </select>
                    @error('semester_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">College</label>
                    <select name="college_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select College</option>
                        @foreach($colleges ?? [] as $college)
                            <option value="{{ $college->id }}" {{ old('college_id', $enrollment->college_id ?? '') == $college->id ? 'selected' : '' }}>{{ $college->college_name }}</option>
                        @endforeach
                    </select>
                    @error('college_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Department</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $enrollment->department_id ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                    <select name="program_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Program</option>
                        @foreach($programs ?? [] as $program)
                            <option value="{{ $program->id }}" {{ old('program_id', $enrollment->program_id ?? '') == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                        @endforeach
                    </select>
                    @error('program_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                    <select name="year_level" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Year Level</option>
                        @foreach([1,2,3,4] as $yl)
                            <option value="{{ $yl }}" {{ old('year_level', $enrollment->year_level ?? '') == $yl ? 'selected' : '' }}>{{ \App\Models\EnrollmentRecord::yearLevelLabel($yl) }}</option>
                        @endforeach
                    </select>
                    @error('year_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment Type</label>
                    <select name="enrollment_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Type</option>
                        @foreach(['new','returning','continuing','transferee','graduating','withdrawn','dropped'] as $type)
                            <option value="{{ $type }}" {{ old('enrollment_type', $enrollment->enrollment_type ?? '') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    @error('enrollment_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment Count</label>
                    <input type="number" name="enrollment_count" min="0" required
                           value="{{ old('enrollment_count', $enrollment->enrollment_count ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('enrollment_count') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    {{ isset($enrollment) ? 'Update Record' : 'Save Record' }}
                </button>
                <a href="{{ route('enrollment.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
