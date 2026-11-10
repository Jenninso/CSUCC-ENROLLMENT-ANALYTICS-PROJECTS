@extends('layouts.app')
@section('title', 'Import Enrollment Data')

@section('content')
<div class="max-w-2xl bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold mb-4">Import CSV File</h3>
    <p class="text-sm text-gray-500 mb-4">Required columns: academic_year_id, semester_id, college_id, department_id, program_id, year_level, enrollment_type, enrollment_count</p>
    <form method="POST" action="{{ route('enrollment-records.import') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="file" name="file" accept=".csv,.txt" required class="w-full border rounded px-3 py-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Upload & Import</button>
    </form>
    @if(session('import_errors'))
        <div class="mt-4 p-3 bg-amber-50 text-amber-800 rounded text-sm">
            @foreach(session('import_errors') as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
</div>
@endsection
