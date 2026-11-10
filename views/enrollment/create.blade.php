@extends('layouts.app')
@section('title', isset($enrollmentRecord) ? 'Edit Record' : 'Add Record')

@section('content')
<div class="max-w-2xl bg-white rounded-lg shadow-sm p-6">
    <form method="POST" action="{{ isset($enrollmentRecord) ? route('enrollment-records.update', $enrollmentRecord) : route('enrollment-records.store') }}" class="space-y-4">
        @csrf
        @if(isset($enrollmentRecord)) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Academic Year</label>
                <select name="academic_year_id" required class="w-full border rounded px-3 py-2">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $enrollmentRecord->academic_year_id ?? '') == $year->id)>{{ $year->academic_year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Semester</label>
                <select name="semester_id" required class="w-full border rounded px-3 py-2">
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected(old('semester_id', $enrollmentRecord->semester_id ?? '') == $semester->id)>{{ $semester->semester_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">College</label>
                <select name="college_id" required class="w-full border rounded px-3 py-2">
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" @selected(old('college_id', $enrollmentRecord->college_id ?? '') == $college->id)>{{ $college->college_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Department</label>
                <select name="department_id" required class="w-full border rounded px-3 py-2">
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id', $enrollmentRecord->department_id ?? '') == $department->id)>{{ $department->department_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Program</label>
                <select name="program_id" required class="w-full border rounded px-3 py-2">
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id', $enrollmentRecord->program_id ?? '') == $program->id)>{{ $program->program_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Year Level</label>
                <input type="number" name="year_level" min="1" max="6" value="{{ old('year_level', $enrollmentRecord->year_level ?? 1) }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Enrollment Type</label>
                <select name="enrollment_type" required class="w-full border rounded px-3 py-2">
                    @foreach(['new','returning','continuing','transferee','graduating'] as $type)
                        <option value="{{ $type }}" @selected(old('enrollment_type', $enrollmentRecord->enrollment_type ?? '') == $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Enrollment Count</label>
                <input type="number" name="enrollment_count" min="0" value="{{ old('enrollment_count', $enrollmentRecord->enrollment_count ?? 0) }}" required class="w-full border rounded px-3 py-2">
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button>
            <a href="{{ route('enrollment-records.index') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a>
        </div>
    </form>
</div>
@endsection
