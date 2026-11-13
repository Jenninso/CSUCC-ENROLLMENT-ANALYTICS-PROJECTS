@extends('layouts.app')
@section('title', 'Student Records')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">Student Records</h3>
        <p class="ui-page-sub">One record per student per term, identified by Student ID only. CEIT Program courses include Information Technology, Computer Engineering, and Electrical Engineering.</p>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <a href="{{ route('students.export', request()->query()) }}" class="ui-btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export
        </a>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <a href="{{ route('students.import') }}" class="ui-btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Import
        </a>
        <a href="{{ route('students.create') }}" class="ui-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Student
        </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Records</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['total'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Average GPA</p>
        <p class="ui-kpi-value text-xl">{{ $summary['avg_gpa'] !== null ? number_format($summary['avg_gpa'], 2) : '—' }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Average Attendance</p>
        <p class="ui-kpi-value text-xl">{{ $summary['avg_attendance'] !== null ? number_format($summary['avg_attendance'], 1).'%' : '—' }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">High Risk</p>
        <p class="ui-kpi-value text-xl text-orange-700">{{ number_format($summary['high'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Critical Risk</p>
        <p class="ui-kpi-value text-xl text-red-700">{{ number_format($summary['critical'] ?? 0) }}</p>
    </div>
</div>

<div class="ui-panel p-4 mb-4 text-xs text-gray-600 flex flex-wrap gap-x-4 gap-y-2">
    <span class="font-semibold text-gray-700 uppercase tracking-wide text-[10px]">Risk level</span>
    <span><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">Low</span> {{ number_format($summary['low'] ?? 0) }}</span>
    <span><span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">Moderate</span> {{ number_format($summary['moderate'] ?? 0) }}</span>
    <span><span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 font-medium">High</span> GPA 2.75+, 2 failed subjects, attendance under 75%, or unpaid tuition</span>
    <span><span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Critical</span> GPA above 3.00, 3+ failed subjects, attendance under 60%, or dropped/withdrawn</span>
</div>

<div class="ui-panel p-4 mb-6">
    <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Student ID" class="ui-input">
        <select name="academic_year_id" class="ui-input">
            <option value="">All Academic Years</option>
            @foreach($academicYears ?? [] as $ay)
                <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->academic_year }}</option>
            @endforeach
        </select>
        <select name="semester_id" class="ui-input">
            <option value="">All Semesters</option>
            @foreach($semesters ?? [] as $sem)
                <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>{{ $sem->academicYear->academic_year ?? '' }} — {{ $sem->semester_name }}</option>
            @endforeach
        </select>
        <select name="college_id" class="ui-input">
            <option value="">All Colleges</option>
            @foreach($colleges ?? [] as $college)
                <option value="{{ $college->id }}" {{ request('college_id') == $college->id ? 'selected' : '' }}>{{ $college->college_code }} — {{ $college->college_name }}</option>
            @endforeach
        </select>
        <select name="program_id" class="ui-input">
            <option value="">All Courses</option>
            @foreach($programs ?? [] as $program)
                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->program_code }} — {{ $program->program_name }}</option>
            @endforeach
        </select>
        <select name="year_level" class="ui-input">
            <option value="">All Year Levels</option>
            @for($i = 1; $i <= 4; $i++)
                <option value="{{ $i }}" {{ request('year_level') == $i ? 'selected' : '' }}>{{ \App\Models\EnrollmentRecord::yearLevelLabel($i) }}</option>
            @endfor
        </select>
        <select name="section" class="ui-input">
            <option value="">All Sections</option>
            @foreach($sections ?? [] as $section)
                <option value="{{ $section }}" {{ request('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
            @endforeach
        </select>
        <select name="gender" class="ui-input">
            <option value="">All Genders</option>
            @foreach(\App\Models\StudentRecord::GENDERS as $value => $label)
                <option value="{{ $value }}" {{ request('gender') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="enrolled_status" class="ui-input">
            <option value="">All Enrollment Statuses</option>
            @foreach(\App\Models\StudentRecord::ENROLLED_STATUSES as $value => $label)
                <option value="{{ $value }}" {{ request('enrolled_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="student_type" class="ui-input">
            <option value="">All Student Types</option>
            @foreach(\App\Models\StudentRecord::STUDENT_TYPES as $value => $label)
                <option value="{{ $value }}" {{ request('student_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="scholarship_status" class="ui-input">
            <option value="">All Scholarship Statuses</option>
            @foreach(\App\Models\StudentRecord::SCHOLARSHIP_STATUSES as $value => $label)
                <option value="{{ $value }}" {{ request('scholarship_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="tuition_status" class="ui-input">
            <option value="">All Tuition Statuses</option>
            @foreach(\App\Models\StudentRecord::TUITION_STATUSES as $value => $label)
                <option value="{{ $value }}" {{ request('tuition_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="risk_level" class="ui-input">
            <option value="">All Risk Levels</option>
            @foreach(\App\Models\StudentRecord::RISK_LEVELS as $value => $label)
                <option value="{{ $value }}" {{ request('risk_level') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="ui-btn-primary flex-1">Filter</button>
            <a href="{{ route('students.index') }}" class="ui-btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="ui-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="ui-table whitespace-nowrap">
            <thead>
                <tr>
                    <th class="sticky left-0 z-10">Student ID</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>College</th>
                    <th>Group</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th class="text-right">GPA</th>
                    <th class="text-right">Average Grade</th>
                    <th class="text-right">Attendance %</th>
                    <th class="text-right">Subjects</th>
                    <th class="text-right">Failed</th>
                    <th class="text-right">Units</th>
                    <th>Enrolled Status</th>
                    <th>Student Type</th>
                    <th>Scholarship</th>
                    <th>Tuition</th>
                    <th>Risk Level</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="font-mono font-medium sticky left-0 bg-[var(--theme-panel)]">
                        <a href="{{ route('students.show', $student) }}" class="ui-link">{{ $student->student_id }}</a>
                    </td>
                    <td>{{ $student->genderLabel() }}</td>
                    <td>{{ $student->age }}</td>
                    <td class="font-medium">{{ $student->program?->department?->college?->college_code ?? '—' }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->academicGroup() === 'Engineering' ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $student->academicGroup() }}</span>
                    </td>
                    <td>{{ $student->program->program_code ?? '—' }}</td>
                    <td>{{ \App\Models\EnrollmentRecord::yearLevelLabel($student->year_level) }}</td>
                    <td>{{ $student->section }}</td>
                    <td>{{ $student->academicYear->academic_year ?? '—' }}</td>
                    <td>{{ $student->semester->semester_name ?? '—' }}</td>
                    <td class="text-right tabular-nums">{{ number_format($student->gpa, 2) }}</td>
                    <td class="text-right tabular-nums">{{ number_format($student->average_grade, 2) }}</td>
                    <td class="text-right tabular-nums">{{ number_format($student->attendance_percent, 1) }}%</td>
                    <td class="text-right tabular-nums">{{ $student->subjects_count }}</td>
                    <td class="text-right tabular-nums">{{ $student->failed_subjects }}</td>
                    <td class="text-right tabular-nums">{{ number_format($student->units_enrolled, 1) }}</td>
                    <td>{{ $student->enrolledLabel() }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span>
                    </td>
                    <td>{{ $student->scholarshipLabel() }}</td>
                    <td>{{ $student->tuitionLabel() }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $student->riskBadgeClass() }}">{{ $student->riskLabel() }}</span>
                    </td>
                    <td class="text-center align-top">
                        <div
                            class="relative inline-block text-left"
                            x-data="{
                                open: false,
                                panelStyle: '',
                                toggle(event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (this.open) {
                                        this.open = false;
                                        return;
                                    }
                                    window.dispatchEvent(new CustomEvent('close-actions'));
                                    const r = event.currentTarget.getBoundingClientRect();
                                    const spaceBelow = window.innerHeight - r.bottom;
                                    const openUp = spaceBelow < 180 && r.top > spaceBelow;
                                    const left = Math.max(8, Math.min(r.right - 176, window.innerWidth - 184));
                                    this.panelStyle = openUp
                                        ? `position:fixed;left:${left}px;bottom:${window.innerHeight - r.top + 4}px;z-index:9999;`
                                        : `position:fixed;left:${left}px;top:${r.bottom + 4}px;z-index:9999;`;
                                    this.open = true;
                                }
                            }"
                            @close-actions.window="open = false"
                            @keydown.escape.window="open = false"
                        >
                            <button
                                type="button"
                                @click="toggle($event)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                :aria-expanded="open"
                                aria-haspopup="true"
                            >
                                Actions
                                <svg class="w-3.5 h-3.5 text-gray-500 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <template x-teleport="body">
                                <div
                                    x-show="open"
                                    x-transition
                                    @click.outside="open = false"
                                    :style="panelStyle"
                                    class="w-44 rounded-lg border border-gray-200 dark-panel py-1 shadow-2xl text-left"
                                    style="background: var(--theme-panel); color: var(--theme-text); z-index: 9999;"
                                >
                                    <div class="px-3 py-2 border-b border-gray-100">
                                        <p class="text-[10px] font-mono text-gray-500">{{ $student->student_id }}</p>
                                    </div>
                                    <a href="{{ route('students.show', $student) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" @click="open = false">View</a>
                                    @if(auth()->user()?->canAccess('enrollment.manage'))
                                    <a href="{{ route('students.edit', $student) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" @click="open = false">Edit</a>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete the record for {{ $student->student_id }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="22" class="px-4 py-10 text-center text-gray-400">No student records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="px-4 py-3 border-t">{{ $students->links() }}</div>
    @endif
</div>
@endsection
