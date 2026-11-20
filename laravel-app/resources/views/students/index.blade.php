@extends('layouts.app')
@section('title', 'Student Records')
@section('hidePageHead', '1')

@section('content')
<div class="page-head">
    <div>
        <p class="page-kicker">CSUCC · CEIT</p>
        <h3 class="ui-page-title">Student Records</h3>
        <p class="ui-page-sub">One record per student per term by Student ID. Open a row for full details.</p>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <a href="{{ route('students.export', request()->query()) }}" class="ui-btn-primary">Export</a>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <a href="{{ route('students.create') }}" class="ui-btn-primary">Add one (advanced)</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="ui-kpi ui-kpi-info">
        <p class="ui-kpi-label">Records</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['total'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi ui-kpi-sky">
        <p class="ui-kpi-label">Average GPA</p>
        <p class="ui-kpi-value text-xl">{{ $summary['avg_gpa'] !== null ? number_format($summary['avg_gpa'], 2) : '—' }}</p>
    </div>
    <div class="ui-kpi ui-kpi-alert">
        <p class="ui-kpi-label">High Risk</p>
        <p class="ui-kpi-value text-xl text-orange-700">{{ number_format($summary['high'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi ui-kpi-bad">
        <p class="ui-kpi-label">Critical Risk</p>
        <p class="ui-kpi-value text-xl text-red-700">{{ number_format($summary['critical'] ?? 0) }}</p>
    </div>
</div>

<div class="ui-panel p-3 mb-4 text-xs text-gray-600 flex flex-wrap gap-x-4 gap-y-2 items-center">
    <span class="font-bold text-gray-800 uppercase tracking-wide text-[10px]">Risk guide</span>
    <span><span class="ui-chip ui-chip-good">Low</span> {{ number_format($summary['low'] ?? 0) }}</span>
    <span><span class="ui-chip ui-chip-warn">Moderate</span> {{ number_format($summary['moderate'] ?? 0) }}</span>
    <span><span class="ui-chip ui-chip-alert">High</span> GPA 2.75+ or 2 failed</span>
    <span><span class="ui-chip ui-chip-bad">Critical</span> GPA 3.00+, 3+ failed, or dropped</span>
</div>

<div class="ui-panel p-3 mb-3 sticky top-2 z-20" style="backdrop-filter: blur(10px); background: color-mix(in srgb, var(--theme-panel) 94%, transparent);">
    <form method="GET" action="{{ route('students.index') }}">
        <div class="flex flex-nowrap gap-2 items-center">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Student ID" class="ui-input min-w-0 flex-1 basis-0">
            <select name="academic_year_id" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Academic Year</option>
                @foreach($academicYears ?? [] as $ay)
                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->academic_year }}</option>
                @endforeach
            </select>
            <select name="program_id" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Course</option>
                @foreach($programs ?? [] as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->program_code }}</option>
                @endforeach
            </select>
            <select name="year_level" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Year Level</option>
                @for($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}" {{ request('year_level') == $i ? 'selected' : '' }}>{{ \App\Models\EnrollmentRecord::yearLevelLabel($i) }}</option>
                @endfor
            </select>
            <select name="enrolled_status" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Enrollment Status</option>
                @foreach(\App\Models\StudentRecord::ENROLLED_STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ request('enrolled_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="student_type" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Student Type</option>
                @foreach(\App\Models\StudentRecord::STUDENT_TYPES as $value => $label)
                    <option value="{{ $value }}" {{ request('student_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="risk_level" class="ui-input min-w-0 flex-1 basis-0">
                <option value="">Risk Level</option>
                @foreach(\App\Models\StudentRecord::RISK_LEVELS as $value => $label)
                    <option value="{{ $value }}" {{ request('risk_level') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 shrink-0">
                <button type="submit" class="ui-btn-filter">Filter</button>
                <a href="{{ route('students.index') }}" class="ui-btn-clear">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="ui-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="ui-table whitespace-nowrap">
            <thead>
                <tr>
                    <th class="sticky left-0 z-10">Student ID</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th class="hidden md:table-cell">Term</th>
                    <th class="hidden sm:table-cell">Type</th>
                    <th class="hidden lg:table-cell">Standing</th>
                    <th class="text-right">GPA</th>
                    <th>Risk</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="font-mono font-bold sticky left-0 bg-[var(--theme-panel)]">
                        <a href="{{ route('students.show', $student) }}" class="ui-link">{{ $student->student_id }}</a>
                    </td>
                    <td class="font-semibold">{{ $student->program->program_code ?? '—' }}</td>
                    <td>{{ \App\Models\EnrollmentRecord::yearLevelLabel($student->year_level) }}</td>
                    <td class="hidden md:table-cell text-xs text-gray-600">
                        {{ $student->academicYear->academic_year ?? '—' }}
                        <span class="block text-[11px]">{{ $student->semester->semester_name ?? '' }}</span>
                    </td>
                    <td class="hidden sm:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $student->studentTypeBadgeClass() }}">{{ $student->studentTypeLabel() }}</span>
                    </td>
                    <td class="hidden lg:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $student->academicStandingBadgeClass() }}">{{ $student->academicStandingLabel() }}</span>
                    </td>
                    <td class="text-right tabular-nums font-semibold">{{ number_format($student->gpa, 2) }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $student->riskBadgeClass() }}">{{ $student->riskLabel() }}</span>
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
                                    if (this.open) { this.open = false; return; }
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
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-teal-900 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100"
                                :aria-expanded="open"
                            >
                                Actions
                                <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <template x-teleport="body">
                                <div
                                    x-show="open"
                                    x-transition
                                    @click.outside="open = false"
                                    :style="panelStyle"
                                    class="w-44 rounded-lg border border-gray-200 py-1 shadow-2xl text-left"
                                    style="background: var(--theme-panel); color: var(--theme-text); z-index: 9999;"
                                >
                                    <div class="px-3 py-2 border-b border-gray-100">
                                        <p class="text-[10px] font-mono text-gray-500">{{ $student->student_id }}</p>
                                    </div>
                                    <a href="{{ route('students.show', $student) }}" class="block px-3 py-2 text-xs font-bold ui-link hover:bg-gray-50" @click="open = false">View full details</a>
                                    @if(auth()->user()?->canAccess('enrollment.manage'))
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete the record for {{ $student->student_id }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center">
                        <p class="text-base font-bold text-gray-800 mb-1">No student records found</p>
                        <p class="text-sm text-gray-500 mb-4">Import the CEIT master CSV/Excel, or clear filters and try again.</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            @if(auth()->user()?->canAccess('enrollment.manage'))
                            <a href="{{ route('students.import') }}" class="ui-btn-filter">Import Excel / CSV</a>
                            @endif
                            <a href="{{ route('students.index') }}" class="ui-btn-clear">Clear filters</a>
                        </div>
                    </td>
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
