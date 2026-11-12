@extends('layouts.app')
@section('title', 'Student '.$student->student_id)

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <a href="{{ route('students.index') }}" class="ui-link text-sm">← Back to student records</a>
        <h3 class="ui-page-title mt-2 font-mono">{{ $student->student_id }}</h3>
        <p class="ui-page-sub">Identified by Student ID only. No personal name is stored on this record.</p>
    </div>
    @if(auth()->user()?->canAccess('enrollment.manage'))
    <div class="flex gap-2 shrink-0">
        <a href="{{ route('students.edit', $student) }}" class="ui-btn-primary">Edit</a>
        <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete the record for {{ $student->student_id }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Delete</button>
        </form>
    </div>
    @endif
</div>

<div class="ui-panel p-6 max-w-4xl">
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap pb-5 border-b border-gray-100">
        <div>
            <p class="text-[10px] uppercase tracking-[0.14em] font-semibold text-gray-400">Student ID</p>
            <h3 class="text-2xl font-semibold font-mono text-gray-900 mt-1">{{ $student->student_id }}</h3>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $student->riskBadgeClass() }}">{{ $student->riskLabel() }} risk</span>
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-5 text-sm">
        <div>
            <dt class="ui-kpi-label mb-1">Gender</dt>
            <dd class="font-semibold text-gray-900">{{ $student->genderLabel() }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Age</dt>
            <dd class="font-semibold text-gray-900">{{ $student->age }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">College</dt>
            <dd class="font-semibold text-gray-900">{{ $student->program?->department?->college?->college_code ?? '—' }}{{ $student->program?->department?->college ? ' — '.$student->program->department->college->college_name : '' }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Group</dt>
            <dd class="font-semibold text-gray-900">{{ $student->academicGroup() }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Course</dt>
            <dd class="font-semibold text-gray-900">{{ $student->program->program_code ?? '—' }}{{ $student->program ? ' — '.$student->program->program_name : '' }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Year Level</dt>
            <dd class="font-semibold text-gray-900">{{ \App\Models\EnrollmentRecord::yearLevelLabel($student->year_level) }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Section</dt>
            <dd class="font-semibold text-gray-900">{{ $student->section }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Academic Year</dt>
            <dd class="font-semibold text-gray-900">{{ $student->academicYear->academic_year ?? '—' }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Semester</dt>
            <dd class="font-semibold text-gray-900">{{ $student->semester->semester_name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">GPA</dt>
            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($student->gpa, 2) }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Average Grade</dt>
            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($student->average_grade, 2) }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Attendance %</dt>
            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($student->attendance_percent, 1) }}%</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Number of Subjects</dt>
            <dd class="font-semibold text-gray-900">{{ $student->subjects_count }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Failed Subjects</dt>
            <dd class="font-semibold text-gray-900">{{ $student->failed_subjects }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Units Enrolled</dt>
            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($student->units_enrolled, 1) }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Enrolled Status</dt>
            <dd class="font-semibold text-gray-900">{{ $student->enrolledLabel() }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Student Type</dt>
            <dd class="font-semibold text-gray-900">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span>
            </dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Scholarship Status</dt>
            <dd class="font-semibold text-gray-900">{{ $student->scholarshipLabel() }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Tuition Status</dt>
            <dd class="font-semibold text-gray-900">{{ $student->tuitionLabel() }}</dd>
        </div>
        <div>
            <dt class="ui-kpi-label mb-1">Student Risk Level</dt>
            <dd class="font-semibold text-gray-900">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->riskBadgeClass() }}">{{ $student->riskLabel() }}</span>
            </dd>
        </div>
    </dl>
</div>

<div class="ui-panel p-5 max-w-4xl mt-4">
    <div class="flex items-start justify-between gap-3 mb-2">
        <h3 class="text-base font-semibold text-gray-800">At-risk analysis</h3>
        <a href="{{ route('at-risk.show', $student) }}" class="ui-link text-sm">Full report</a>
    </div>
    <p class="text-sm text-gray-700 leading-relaxed">{{ $analysis['narrative'] }}</p>
</div>
@endsection
