@extends('layouts.app')
@section('title', 'SPR')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">SPR</h3>
        <p class="ui-page-sub">Student Personal Record. The printed form is one long bond page, 8.5 × 13 inches, with the CSU Cabadbaran header.</p>
    </div>
</div>

<div class="ui-panel p-4 mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
    <div class="grow">
        <p class="text-sm font-semibold text-gray-900">Share link for students</p>
        <p class="text-xs text-gray-500 mt-1">Students do not sign in. They only see the SPR form, fill it, and save it.</p>
    </div>
    <input type="text" readonly value="{{ route('spr.public') }}" class="ui-input sm:max-w-md" onclick="this.select()">
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Applications</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['total'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Submitted</p>
        <p class="ui-kpi-value text-xl">{{ number_format($summary['submitted'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Approved</p>
        <p class="ui-kpi-value text-xl text-emerald-700">{{ number_format($summary['approved'] ?? 0) }}</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Employed</p>
        <p class="ui-kpi-value text-xl text-sky-700">{{ number_format($summary['employed'] ?? 0) }}</p>
    </div>
</div>

<div class="ui-panel p-4 mb-6">
    <form method="GET" action="{{ route('spes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, Student ID, or reference" class="ui-input lg:col-span-2">
        <select name="status" class="ui-input">
            <option value="">All statuses</option>
            @foreach(\App\Models\SpesApplication::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="application_year" class="ui-input">
            <option value="">All years</option>
            @foreach($years as $year)
                <option value="{{ $year }}" {{ (string) request('application_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <button type="submit" class="ui-btn-primary">Filter</button>
            <a href="{{ route('spes.index') }}" class="ui-btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="ui-panel overflow-x-auto">
    <table class="ui-table">
        <thead>
            <tr>
                <th class="text-left">Reference</th>
                <th class="text-left">Applicant</th>
                <th class="text-left">Student ID</th>
                <th class="text-left">Filed</th>
                <th class="text-left">Documents</th>
                <th class="text-left">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
            <tr>
                <td class="font-mono text-xs">{{ $application->reference_no }}</td>
                <td>
                    <a href="{{ route('spes.show', $application) }}" class="ui-link">{{ $application->fullName() }}</a>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $application->course ?: \App\Models\SpesApplication::EDUCATION_LEVELS[$application->education_level] }} · {{ $application->year_level }}</p>
                </td>
                <td class="font-mono text-xs">{{ $application->student_id ?: '—' }}</td>
                <td>{{ $application->application_date?->format('M j, Y') }}</td>
                <td>{{ $application->documentsAttached() }}/{{ count(\App\Models\SpesApplication::DOCUMENTS) }}</td>
                <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $application->statusBadgeClass() }}">{{ $application->statusLabel() }}</span></td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('spes.show', $application) }}" class="ui-link text-xs">View</a>
                    <a href="{{ route('spes.print', $application) }}" class="ui-link text-xs ml-3" target="_blank">Print</a>
                    <a href="{{ route('spes.pdf', $application) }}" class="ui-link text-xs ml-3">PDF</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500 py-10">No SPR forms yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $applications->links() }}</div>
@endsection
