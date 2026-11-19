@extends('layouts.app')
@section('title', 'Import SPR')
@section('hidePageHead', '1')

@section('content')
<div class="max-w-3xl" x-data="{
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
            this.fileError = 'File is over 200 MB (' + (f.size / (1024 * 1024)).toFixed(1) + ' MB).';
            e.target.value = '';
            return;
        }
        this.fileName = f.name;
    }
}">
    <div class="page-head">
        <div>
            <a href="{{ route('spes.index') }}" class="ui-link text-sm">← Back to SPR</a>
            <p class="page-kicker mt-2">CSUCC · CEIT</p>
            <h3 class="ui-page-title">Import SPR file</h3>
            <p class="ui-page-sub">Upload a CSV or Excel (.xlsx). Each row becomes an SPR with form fields filled from the file. One-row imports open the form right away.</p>
        </div>
        <a href="{{ route('spes.template') }}" class="ui-btn-secondary shrink-0">Download template</a>
    </div>

    <div class="ui-panel p-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 mb-5 text-sm text-slate-600">
            <p class="font-semibold text-slate-700 mb-2">Required columns</p>
            <p class="text-xs sm:text-sm">last_name, first_name, birth_date <span class="text-slate-500">(or date_of_birth)</span></p>
            <p class="mt-2 text-xs text-slate-500">Optional: id_no / student_id, middle_name, address, parents, course / priority_1, documents (yes/no), and other SPR form columns from the template or Export CSV.</p>
            <p class="mt-2 text-xs text-slate-500">Use <span class="font-semibold text-slate-700">Download template</span> or Export from SPR — the student master CSV is a different format. Duplicate Student ID or same name + birth date rows are skipped.</p>
        </div>

        <form method="POST" action="{{ route('spes.import.store') }}" enctype="multipart/form-data" @submit="uploading = true">
            @csrf
            <label class="block cursor-pointer rounded-xl border-2 border-dashed border-teal-200 bg-gradient-to-b from-teal-50/70 to-white p-8 text-center hover:border-teal-400 transition">
                <svg class="w-11 h-11 text-teal-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <p class="text-sm font-semibold text-teal-900" x-text="fileName || 'Click to select CSV or Excel (.xlsx)'"></p>
                <p class="text-xs text-teal-700/80 mt-1">Max 200 MB · form fields filled from each row</p>
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                       class="sr-only" @change="onFile($event)">
            </label>
            <p x-show="fileError" x-cloak class="text-red-500 text-xs mt-2" x-text="fileError"></p>
            @error('file') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror

            <button type="submit" class="ui-btn-filter mt-4" :disabled="uploading || !!fileError || !fileName">
                <svg x-show="uploading" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="uploading ? 'Importing…' : 'Upload & fill SPR forms'"></span>
            </button>
        </form>
    </div>
</div>
@endsection
