@extends('layouts.app')
@section('title', 'Enrollment Records')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <p class="text-gray-500 text-sm">View and manage enrollment records.</p>
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <a href="{{ route('enrollment.import') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Import
        </a>
        @endif
        <a href="{{ route('enrollment.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export
        </a>
        @if(auth()->user()?->canAccess('enrollment.manage'))
        <a href="{{ route('enrollment.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Record
        </a>
        @endif
    </div>
</div>

{{-- Filter Bar --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('enrollment.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <select name="academic_year_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Academic Years</option>
            @foreach($academicYears ?? [] as $ay)
                <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year_name ?? $ay->academic_year }}</option>
            @endforeach
        </select>
        <select name="semester_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Semesters</option>
            @foreach($semesters ?? [] as $sem)
                <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>{{ $sem->semester_name }}</option>
            @endforeach
        </select>
        <select name="college_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Colleges</option>
            @foreach($colleges ?? [] as $college)
                <option value="{{ $college->id }}" {{ request('college_id') == $college->id ? 'selected' : '' }}>{{ $college->college_name }}</option>
            @endforeach
        </select>
        <select name="program_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Programs</option>
            @foreach($programs ?? [] as $program)
                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
            @endforeach
        </select>
        <select name="year_level" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">All Year Levels</option>
            @for($i = 1; $i <= 4; $i++)
                <option value="{{ $i }}" {{ request('year_level') == $i ? 'selected' : '' }}>{{ \App\Models\EnrollmentRecord::yearLevelLabel($i) }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Filter</button>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Academic Year</th>
                    <th class="px-4 py-3 font-semibold">Semester</th>
                    <th class="px-4 py-3 font-semibold">College</th>
                    <th class="px-4 py-3 font-semibold">Program</th>
                    <th class="px-4 py-3 font-semibold">Year Level</th>
                    <th class="px-4 py-3 font-semibold">Type</th>
                    <th class="px-4 py-3 font-semibold text-right">Count</th>
                    <th class="px-4 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments ?? [] as $record)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3">{{ $record->academicYear->year_name ?? $record->academicYear->academic_year ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $record->semester->semester_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $record->program->department->college->college_name ?? $record->college->college_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $record->program->program_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $record->year_level }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $record->enrollment_type === 'New' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $record->enrollment_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($record->enrollment_count) }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('enrollment.edit', $record) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('enrollment.destroy', $record) }}" onsubmit="return confirm('Delete this record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No enrollment records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($enrollments) && method_exists($enrollments, 'links'))
    <div class="px-4 py-3 border-t">{{ $enrollments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
