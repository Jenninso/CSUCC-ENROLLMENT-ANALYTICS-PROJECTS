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
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">SPES</p>
            <h4 class="mt-1 text-lg font-semibold text-gray-900">Special Program for the Employment of Students</h4>
            <p class="mt-2 text-sm text-gray-600">Fill out the DOLE application for summer or Christmas work. You have {{ $spesCount }} {{ \Illuminate\Support\Str::plural('application', $spesCount) }}.</p>
        </a>
        <a href="{{ route('student.reasons.index') }}" class="ui-panel p-5 block hover:ring-2 hover:ring-emerald-700/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Academic status</p>
            <h4 class="mt-1 text-lg font-semibold text-gray-900">Shift, transfer, or withdrawal</h4>
            <p class="mt-2 text-sm text-gray-600">Explain why you shifted program, transferred, withdrew a subject, or stopped for the term. {{ $reasonCount }} {{ \Illuminate\Support\Str::plural('reason', $reasonCount) }} on file.</p>
        </a>
    </div>
</div>
@endsection
