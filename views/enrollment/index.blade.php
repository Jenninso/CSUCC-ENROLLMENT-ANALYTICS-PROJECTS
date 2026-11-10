@extends('layouts.app')
@section('title', 'Enrollment Records')

@section('content')
<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('enrollment-records.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">Add Record</a>
    <a href="{{ route('enrollment-records.import.form') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">Import</a>
    <a href="{{ route('enrollment-records.export') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm">Export CSV</a>
</div>

<form method="GET" class="bg-white rounded-lg shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
    <select name="academic_year_id" class="border rounded px-3 py-2 text-sm">
        <option value="">All Years</option>
        @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->academic_year }}</option>
        @endforeach
    </select>
    <select name="semester_id" class="border rounded px-3 py-2 text-sm">
        <option value="">All Semesters</option>
        @foreach($semesters as $semester)
            <option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->semester_name }}</option>
        @endforeach
    </select>
    <select name="college_id" class="border rounded px-3 py-2 text-sm">
        <option value="">All Colleges</option>
        @foreach($colleges as $college)
            <option value="{{ $college->id }}" @selected(request('college_id') == $college->id)>{{ $college->college_name }}</option>
        @endforeach
    </select>
    <select name="program_id" class="border rounded px-3 py-2 text-sm">
        <option value="">All Programs</option>
        @foreach($programs as $program)
            <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->program_name }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-indigo-600 text-white rounded px-4 py-2 text-sm">Filter</button>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3">Academic Year</th>
                <th class="px-4 py-3">Semester</th>
                <th class="px-4 py-3">College</th>
                <th class="px-4 py-3">Program</th>
                <th class="px-4 py-3">Year Level</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Count</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
            <tr class="border-t hover:bg-gray-50">
                <td class="px-4 py-3">{{ $record->academicYear?->academic_year }}</td>
                <td class="px-4 py-3">{{ $record->semester?->semester_name }}</td>
                <td class="px-4 py-3">{{ $record->college?->college_name }}</td>
                <td class="px-4 py-3">{{ $record->program?->program_name }}</td>
                <td class="px-4 py-3">{{ $record->year_level }}</td>
                <td class="px-4 py-3 capitalize">{{ $record->enrollment_type }}</td>
                <td class="px-4 py-3">{{ number_format($record->enrollment_count) }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('enrollment-records.edit', $record) }}" class="text-indigo-600 mr-2">Edit</a>
                    <form action="{{ route('enrollment-records.destroy', $record) }}" method="POST" class="inline" onsubmit="return confirm('Delete this record?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No enrollment records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
