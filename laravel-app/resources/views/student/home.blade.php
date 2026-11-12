@extends('layouts.app')
@section('title', 'Student portal')

@section('content')
<div class="max-w-4xl">
    <h3 class="ui-page-title">Hello, {{ auth()->user()->first_name }}</h3>
    <p class="ui-page-sub">Student ID <span class="font-mono font-semibold">{{ $studentId }}</span>
        @if($record)
            · {{ $record->program?->program_name ?? 'Program on file' }}, year {{ $record->year_level }}
        @endif
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <a href="{{ route('student.spes.index') }}" class="ui-panel p-5 block hover:ring-2 hover:ring-emerald-700/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">SPR</p>
            <h4 class="mt-1 text-lg font-semibold text-gray-900">Student Personal Record</h4>
            <p class="mt-2 text-sm text-gray-600">Fill out your personal and scholastic information. You have {{ $spesCount }} {{ \Illuminate\Support\Str::plural('record', $spesCount) }} on file.</p>
        </a>
    </div>
</div>
@endsection
