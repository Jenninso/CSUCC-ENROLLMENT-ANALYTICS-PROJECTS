@extends('layouts.app')
@section('title', 'My SPR forms')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">My SPR forms</h3>
        <p class="ui-page-sub">Student ID {{ $studentId }}. Submit your Student Personal Record. Staff review it after you send it.</p>
    </div>
    <a href="{{ route('student.spes.create') }}" class="ui-btn-primary shrink-0">New application</a>
</div>

<div class="ui-panel overflow-x-auto">
    <table class="ui-table">
        <thead>
            <tr>
                <th class="text-left">Reference</th>
                <th class="text-left">Filed</th>
                <th class="text-left">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
            <tr>
                <td class="font-mono text-xs">{{ $application->reference_no }}</td>
                <td>{{ $application->application_date?->format('M j, Y') }}</td>
                <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $application->statusBadgeClass() }}">{{ $application->statusLabel() }}</span></td>
                <td class="text-right">
                    <a href="{{ route('student.spes.show', $application) }}" class="ui-link text-xs">Open</a>
                    <a href="{{ route('student.spes.pdf', $application) }}" class="ui-link text-xs ml-3">PDF</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-gray-500 py-10">You have not submitted an SPR form yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $applications->links() }}</div>
@endsection
