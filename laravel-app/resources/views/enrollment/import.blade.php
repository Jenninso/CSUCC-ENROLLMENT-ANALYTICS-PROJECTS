@extends('layouts.app')
@section('title', 'Import Enrollment Data')

@section('content')
<div class="max-w-4xl" x-data="{ uploading: false, preview: {{ isset($previewRows) && count($previewRows) ? 'true' : 'false' }} }">
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload File</h3>
        <p class="text-sm text-gray-500 mb-4">CSV only. Required columns: academic_year, semester, program_code, year_level, enrollment_type, enrollment_count.</p>

        <div class="flex flex-wrap gap-3 mb-6">
            <a href="{{ route('enrollment.template') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download Template
            </a>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6 text-sm text-slate-600">
            <p class="font-medium text-slate-700 mb-2">Required columns:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>academic_year, semester, program_code</li>
                <li>year_level (1–6), enrollment_type (new, returning, continuing, shiftee, transferee, graduating, withdrawn, dropped), enrollment_count</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('enrollment.import.preview') }}" enctype="multipart/form-data" @submit="uploading = true">
            @csrf
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition">
                <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-400 mt-2">CSV, XLSX, or XLS — max 10 MB</p>
            </div>
            @error('file') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror

            <button type="submit" class="mt-4 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg inline-flex items-center gap-2">
                <svg x-show="uploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="uploading ? 'Processing...' : 'Upload & Validate'"></span>
            </button>
        </form>
    </div>

    @php $preview = $preview ?? session('import_preview'); @endphp
    @if($preview)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Validation report — score {{ $preview['score'] }}%</h3>
            <div class="flex gap-3 text-sm">
                <span class="text-emerald-600 font-medium">{{ $preview['counts']['valid'] }} valid</span>
                <span class="text-red-600 font-medium">{{ $preview['counts']['invalid'] }} invalid</span>
            </div>
        </div>
        @if($preview['issues'])
        <ul class="text-sm text-red-700 mb-4 list-disc ml-5">
            @foreach($preview['issues'] as $issue)
                <li>Row {{ $issue['line'] }}: {{ implode(' ', $issue['errors']) }}</li>
            @endforeach
        </ul>
        @endif
        @if($preview['counts']['valid'] > 0)
        <form method="POST" action="{{ route('enrollment.import.confirm') }}" class="mt-4">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg">
                Confirm import of {{ $preview['counts']['valid'] }} valid rows
            </button>
        </form>
        @endif
    </div>
    @endif
</div>
@endsection
