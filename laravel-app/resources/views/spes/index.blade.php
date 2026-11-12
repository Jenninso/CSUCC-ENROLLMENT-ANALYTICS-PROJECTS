@extends('layouts.app')
@section('title', 'SPES Applications')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">SPES Applications</h3>
        <p class="ui-page-sub">Special Program for the Employment of Students (RA 7323, as amended). This form keeps the legal identity DOLE needs. Academic records stay identified by Student ID only.</p>
    </div>
    @if(auth()->user()?->canAccess('enrollment.manage'))
    <a href="{{ route('spes.create') }}" class="ui-btn-primary shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        New application
    </a>
    @endif
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
        <select name="work_period" class="ui-input">
            <option value="">All work periods</option>
            @foreach(\App\Models\SpesApplication::WORK_PERIODS as $value => $label)
                <option value="{{ $value }}" {{ request('work_period') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                <th class="text-left">Period</th>
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
                <td>{{ \App\Models\SpesApplication::WORK_PERIODS[$application->work_period] }} {{ $application->application_year }}</td>
                <td>{{ $application->documentsAttached() }}/{{ count(\App\Models\SpesApplication::DOCUMENTS) }}</td>
                <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $application->statusBadgeClass() }}">{{ $application->statusLabel() }}</span></td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('spes.show', $application) }}" class="ui-link text-xs">View</a>
                    <a href="{{ route('spes.print', $application) }}" class="ui-link text-xs ml-3" target="_blank">Print</a>
                    @if(auth()->user()?->canAccess('enrollment.manage'))
                    <a href="{{ route('spes.edit', $application) }}" class="ui-link text-xs ml-3">Edit</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500 py-10">No SPES applications yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $applications->links() }}</div>
@endsection
