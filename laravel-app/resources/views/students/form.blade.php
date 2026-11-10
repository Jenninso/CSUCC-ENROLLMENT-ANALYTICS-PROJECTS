@extends('layouts.app')
@section('title', isset($student) ? 'Edit Student Record' : 'Add Student Record')

@section('content')
<div class="max-w-5xl">
    <p class="text-sm text-gray-500 mb-4">Do not enter a student name. This record is identified by Student ID only. Risk level is calculated when you save.</p>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ isset($student) ? route('students.update', $student) : route('students.store') }}" class="space-y-5">
            @csrf
            @if(isset($student)) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                    <input type="text" name="student_id" value="{{ old('student_id', $student->student_id ?? '') }}" required maxlength="30" placeholder="2025-01001" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('student_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Gender</option>
                        @foreach(\App\Models\StudentRecord::GENDERS as $value => $label)
                            <option value="{{ $value }}" {{ old('gender', $student->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                    <input type="number" name="age" value="{{ old('age', $student->age ?? '') }}" required min="15" max="80" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('age') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @php
                    $selectedProgramId = old('program_id', $student->program_id ?? (($programs ?? collect())->count() === 1 ? $programs->first()->id : ''));
                    $selectedProgram = ($programs ?? collect())->firstWhere('id', (int) $selectedProgramId);
                    $selectedCollegeId = old('college_id', $selectedProgram?->department?->college_id ?? (($colleges ?? collect())->count() === 1 ? $colleges->first()->id : ''));
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">College</label>
                    <select name="college_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select College</option>
                        @foreach($colleges ?? [] as $college)
                            <option value="{{ $college->id }}" {{ (string) $selectedCollegeId === (string) $college->id ? 'selected' : '' }}>{{ $college->college_code }} — {{ $college->college_name }}</option>
                        @endforeach
                    </select>
                    @error('college_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                    <select name="program_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Course</option>
                        @foreach($programs ?? [] as $program)
                            @php $group = $program->department?->department_code === 'IT' ? 'Information Technology' : 'Engineering'; @endphp
                            <option value="{{ $program->id }}" {{ (string) $selectedProgramId === (string) $program->id ? 'selected' : '' }}>{{ $program->program_code }} — {{ $program->program_name }} ({{ $group }})</option>
                        @endforeach
                    </select>
                    @error('program_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                    <select name="year_level" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Year Level</option>
                        @foreach([1, 2, 3, 4] as $level)
                            <option value="{{ $level }}" {{ old('year_level', $student->year_level ?? '') == $level ? 'selected' : '' }}>{{ \App\Models\EnrollmentRecord::yearLevelLabel($level) }}</option>
                        @endforeach
                    </select>
                    @error('year_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                    <input type="text" name="section" value="{{ old('section', $student->section ?? '') }}" required maxlength="20" placeholder="A" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('section') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select name="academic_year_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Academic Year</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay->id }}" {{ old('academic_year_id', $student->academic_year_id ?? '') == $ay->id ? 'selected' : '' }}>{{ $ay->academic_year }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select name="semester_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Semester</option>
                        @foreach($semesters ?? [] as $sem)
                            <option value="{{ $sem->id }}" {{ old('semester_id', $student->semester_id ?? '') == $sem->id ? 'selected' : '' }}>{{ $sem->academicYear->academic_year ?? '' }} — {{ $sem->semester_name }}</option>
                        @endforeach
                    </select>
                    @error('semester_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GPA</label>
                    <input type="number" name="gpa" value="{{ old('gpa', isset($student) ? number_format($student->gpa, 2, '.', '') : '') }}" required min="1" max="5" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">1.00 highest, 3.00 passing line, 5.00 failing.</p>
                    @error('gpa') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Average Grade</label>
                    <input type="number" name="average_grade" value="{{ old('average_grade', isset($student) ? number_format($student->average_grade, 2, '.', '') : '') }}" required min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('average_grade') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attendance %</label>
                    <input type="number" name="attendance_percent" value="{{ old('attendance_percent', isset($student) ? number_format($student->attendance_percent, 2, '.', '') : '') }}" required min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('attendance_percent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Number of Subjects</label>
                    <input type="number" name="subjects_count" value="{{ old('subjects_count', $student->subjects_count ?? '') }}" required min="0" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('subjects_count') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Failed Subjects</label>
                    <input type="number" name="failed_subjects" value="{{ old('failed_subjects', $student->failed_subjects ?? '') }}" required min="0" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('failed_subjects') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Units Enrolled</label>
                    <input type="number" name="units_enrolled" value="{{ old('units_enrolled', isset($student) ? number_format($student->units_enrolled, 1, '.', '') : '') }}" required min="0" max="36" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('units_enrolled') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enrolled Status</label>
                    <select name="enrolled_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Status</option>
                        @foreach(\App\Models\StudentRecord::ENROLLED_STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ old('enrolled_status', $student->enrolled_status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('enrolled_status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student Type</label>
                    <select name="student_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Type</option>
                        @foreach(\App\Models\StudentRecord::STUDENT_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('student_type', $student->student_type ?? 'new') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Returning, Transferee, or Withdraw Acad / Stop for this term.</p>
                    @error('student_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scholarship Status</label>
                    <select name="scholarship_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Scholarship</option>
                        @foreach(\App\Models\StudentRecord::SCHOLARSHIP_STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ old('scholarship_status', $student->scholarship_status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('scholarship_status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tuition Status</label>
                    <select name="tuition_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Select Tuition Status</option>
                        @foreach(\App\Models\StudentRecord::TUITION_STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ old('tuition_status', $student->tuition_status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('tuition_status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student Risk Level</label>
                    <div class="px-3 py-2 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 bg-gray-50">
                        @if(isset($student))
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->riskBadgeClass() }}">{{ $student->riskLabel() }}</span>
                            <span class="block text-xs text-gray-400 mt-1">Recalculated from GPA, attendance, failed subjects, enrollment, and tuition when you save.</span>
                        @else
                            Calculated automatically when this record is saved.
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">{{ isset($student) ? 'Update Record' : 'Save Record' }}</button>
                <a href="{{ isset($student) ? route('students.show', $student) : route('students.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
