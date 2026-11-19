@extends('layouts.app')
@section('title', 'Import Student Records')
@section('hidePageHead', '1')

@section('content')
@php $preview = $preview ?? session('student_import_preview'); @endphp
<div class="max-w-4xl" x-data="{
    uploading: false,
    fileName: '',
    fileError: '',
    maxBytes: 200 * 1024 * 1024,
    onFile(e) {
        const f = e.target.files && e.target.files[0];
        this.fileError = '';
        this.fileName = '';
        if (!f) return;
        if (f.size > this.maxBytes) {
            this.fileError = 'File is over 200 MB (' + (f.size / (1024 * 1024)).toFixed(1) + ' MB). Compress or split the extract, then try again.';
            e.target.value = '';
            return;
        }
        this.fileName = f.name;
    }
}">
    <div class="page-head">
        <div>
            <a href="{{ route('students.index') }}" class="ui-link text-sm">← Back to student records</a>
            <p class="page-kicker mt-2">CSUCC · CEIT</p>
            <h3 class="ui-page-title">Import student records</h3>
            <p class="ui-page-sub">Upload a registrar CSV or Excel (.xlsx). The system maps columns automatically, creates missing academic years/semesters, validates every row, then imports in batches.</p>
        </div>
        <a href="{{ route('students.template') }}" class="ui-btn-secondary shrink-0">Download template</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="ui-kpi ui-kpi-info">
            <p class="ui-kpi-label">Step 1</p>
            <p class="mt-1 text-sm font-semibold text-gray-800">Prepare file</p>
            <p class="text-xs text-gray-500 mt-1">CSV or .xlsx up to 200 MB</p>
        </div>
        <div class="ui-kpi ui-kpi-sky">
            <p class="ui-kpi-label">Step 2</p>
            <p class="mt-1 text-sm font-semibold text-gray-800">Validate</p>
            <p class="text-xs text-gray-500 mt-1">Headers mapped · terms auto-created</p>
        </div>
        <div class="ui-kpi ui-kpi-good">
            <p class="ui-kpi-label">Step 3</p>
            <p class="mt-1 text-sm font-semibold text-gray-800">Confirm import</p>
            <p class="text-xs text-gray-500 mt-1">Chunked insert · duplicates skipped</p>
        </div>
    </div>

    <div class="ui-panel p-5 mb-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="ui-chip ui-chip-info">Upload</span>
            <h3 class="text-base font-semibold text-gray-800">Choose your file</h3>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 mb-5 text-sm text-slate-600">
            <p class="font-semibold text-slate-700 mb-2">Required columns (labels or aliases both work)</p>
            <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm">
                <li>student_id, gender, age, course, year_level, section</li>
                <li>academic_year, semester</li>
                <li>gpa, average_grade, number_of_subjects, failed_subjects, units_enrolled</li>
                <li>enrolled_status, student_type, scholarship_status, tuition_status</li>
            </ul>
            <p class="mt-2 text-xs text-slate-500">Optional: academic_standing, names, email, mobile, address. Risk level is calculated automatically. New academic years and semesters found in the file are created automatically.</p>
            <p class="mt-2 text-xs text-slate-500">Tip: for very large CEIT extracts, upload <span class="font-semibold text-slate-700">CSV</span> when possible — it validates faster and uses less memory than .xlsx.</p>
        </div>

        <form method="POST" action="{{ route('students.import.preview') }}" enctype="multipart/form-data"
              @submit="uploading = true">
            @csrf
            <label class="block cursor-pointer rounded-xl border-2 border-dashed border-teal-200 bg-gradient-to-b from-teal-50/70 to-white p-8 text-center hover:border-teal-400 transition">
                <svg class="w-11 h-11 text-teal-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <p class="text-sm font-semibold text-teal-900" x-text="fileName || 'Click to select CSV or Excel (.xlsx)'"></p>
                <p class="text-xs text-teal-700/80 mt-1">Max 200 MB · large CEIT extracts supported</p>
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                       class="sr-only" @change="onFile($event)">
            </label>
            <p x-show="fileError" x-cloak class="text-red-500 text-xs mt-2" x-text="fileError"></p>
            @error('file') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror

            <button type="submit" class="ui-btn-filter mt-4" :disabled="uploading || !!fileError || !fileName"
                    @click="if (!fileName) { fileError = 'Select a CSV or Excel file first.'; $event.preventDefault(); }">
                <svg x-show="uploading" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="uploading ? 'Validating file…' : 'Upload & validate'"></span>
            </button>
            <p x-show="uploading" x-cloak class="text-xs text-slate-500 mt-2">Large CEIT files can take 1–3 minutes on Render. Keep this tab open — do not refresh.</p>
        </form>
    </div>

    @if($preview)
    <div class="ui-panel p-5 mb-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="ui-chip {{ ($preview['score'] ?? 0) >= 90 ? 'ui-chip-good' : (($preview['score'] ?? 0) >= 70 ? 'ui-chip-warn' : 'ui-chip-bad') }}">
                        Score {{ $preview['score'] }}%
                    </span>
                    @if(!empty($preview['file_name']))
                        <span class="text-xs text-gray-500 font-mono">{{ $preview['file_name'] }}</span>
                    @endif
                </div>
                <h3 class="text-base font-semibold text-gray-800">Validation report</h3>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="ui-chip ui-chip-sky">{{ number_format($preview['counts']['rows'] ?? 0) }} rows</span>
                <span class="ui-chip ui-chip-good">{{ number_format($preview['counts']['valid'] ?? 0) }} valid</span>
                <span class="ui-chip ui-chip-bad">{{ number_format($preview['counts']['invalid'] ?? 0) }} invalid</span>
            </div>
        </div>

        @if(!empty($preview['header']))
        <div class="mb-4">
            <p class="ui-kpi-label mb-2">Detected / mapped columns</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($preview['header'] as $col)
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-mono bg-slate-100 text-slate-700 border border-slate-200">{{ $col }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($preview['issues']))
        <div class="rounded-lg border border-red-100 bg-red-50/70 p-3 mb-4">
            <p class="text-sm font-semibold text-red-800 mb-2">Rows that need fixing</p>
            <ul class="text-sm text-red-700 list-disc ml-5 max-h-64 overflow-y-auto space-y-1">
                @foreach($preview['issues'] as $issue)
                    <li>Row {{ $issue['line'] }}: {{ implode(' ', $issue['errors']) }}</li>
                @endforeach
            </ul>
            @if(!empty($preview['issues_truncated']))
                <p class="text-xs text-red-600 mt-2">Showing the first 200 issues. Fix the file and re-upload for a full pass.</p>
            @endif
        </div>
        @endif

        @if(($preview['counts']['valid'] ?? 0) > 0)
        <form method="POST" action="{{ route('students.import.confirm') }}" x-data="{ confirming: false }" @submit="confirming = true">
            @csrf
            <button type="submit" class="ui-btn-primary" :disabled="confirming">
                <span x-text="confirming ? 'Importing in batches…' : 'Confirm import of {{ number_format($preview['counts']['valid']) }} valid rows'"></span>
            </button>
            <p class="text-xs text-gray-500 mt-2">Duplicates for the same student + year + semester are skipped automatically.</p>
        </form>
        @else
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">No valid rows to import yet. Fix the listed issues, then upload again.</p>
        @endif
    </div>
    @endif
</div>
@endsection
