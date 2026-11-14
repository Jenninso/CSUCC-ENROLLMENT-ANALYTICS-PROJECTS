@extends('layouts.app')
@section('title', 'Reports')
@section('subtitle', 'Choose a report, a date range, and an export format.')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Generate Report</h3>
        <p class="text-sm text-gray-500 mb-6">Select a report type, date range, and export format.</p>

        <form method="POST" action="{{ route('reports.generate') }}" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                <select name="report_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Select report type</option>
                    <option value="executive">Executive summary</option>
                    <option value="enrollment_summary">Enrollment Summary</option>
                    <option value="program_analysis">Program Analytics</option>
                    <option value="college_analysis">College Analytics</option>
                    <option value="retention">Retention Report</option>
                </select>
                @error('report_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ old('date_from') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ old('date_to') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select name="academic_year_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year_name ?? $ay->academic_year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">College</label>
                    <select name="college_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        @foreach($colleges ?? [] as $college)
                            <option value="{{ $college->id }}" {{ old('college_id') == $college->id ? 'selected' : '' }}>{{ $college->college_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="format" value="pdf" checked class="text-indigo-600 focus:ring-indigo-500"> PDF
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="format" value="xlsx" class="text-indigo-600 focus:ring-indigo-500"> Excel
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="format" value="csv" class="text-indigo-600 focus:ring-indigo-500"> CSV
                    </label>
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg inline-flex items-center gap-2">
                <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="loading ? 'Generating...' : 'Generate Report'"></span>
            </button>
        </form>
    </div>
</div>
@endsection
