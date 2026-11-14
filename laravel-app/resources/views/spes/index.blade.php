@extends('layouts.app')
@section('title', 'SPR')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <h3 class="ui-page-title">SPR</h3>
        <p class="ui-page-sub">Student Personal Record. The printed form is one long bond page, 8.5 × 13 inches, with the CSU Cabadbaran header.</p>
    </div>
</div>

<div class="ui-panel p-4 mb-4 flex flex-col lg:flex-row lg:items-center gap-4">
    <div class="grow min-w-0">
        <p class="text-sm font-semibold text-gray-900">Share link for students</p>
        <p class="text-xs text-gray-500 mt-1">Students do not sign in. They only see the SPR form, fill it, and save it. Print the QR code and post it where students can scan it.</p>
        <input type="text" readonly value="{{ $shareUrl }}" class="ui-input mt-3" onclick="this.select()">
    </div>
    <div class="flex items-center gap-3 shrink-0">
        <img src="{{ $shareQr }}" alt="QR code for the student SPR form" width="132" height="132" class="bg-white border border-gray-200 rounded-md">
        <a href="{{ route('spes.share-qr') }}" target="_blank" class="ui-btn-secondary">Print QR</a>
    </div>
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
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Student ID or reference" class="ui-input lg:col-span-2">
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
                    <a href="{{ route('spes.show', $application) }}" class="ui-link font-mono">{{ $application->student_id ?: '—' }}</a>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $application->course ?: \App\Models\SpesApplication::EDUCATION_LEVELS[$application->education_level] }} · {{ $application->year_level }}</p>
                </td>
                <td>{{ $application->application_date?->format('M j, Y') }}</td>
                <td>{{ $application->documentsAttached() }}/{{ count(\App\Models\SpesApplication::DOCUMENTS) }}</td>
                <td><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $application->statusBadgeClass() }}">{{ $application->statusLabel() }}</span></td>
                <td class="text-right">
                    <div
                        class="relative inline-block text-left"
                        x-data="{
                            open: false,
                            panelStyle: '',
                            toggle(event) {
                                event.preventDefault();
                                event.stopPropagation();
                                if (this.open) {
                                    this.open = false;
                                    return;
                                }
                                window.dispatchEvent(new CustomEvent('close-actions'));
                                const r = event.currentTarget.getBoundingClientRect();
                                const spaceBelow = window.innerHeight - r.bottom;
                                const openUp = spaceBelow < 200 && r.top > spaceBelow;
                                const left = Math.max(8, Math.min(r.right - 176, window.innerWidth - 184));
                                this.panelStyle = openUp
                                    ? `position:fixed;left:${left}px;bottom:${window.innerHeight - r.top + 4}px;z-index:9999;`
                                    : `position:fixed;left:${left}px;top:${r.bottom + 4}px;z-index:9999;`;
                                this.open = true;
                            }
                        }"
                        @close-actions.window="open = false"
                        @keydown.escape.window="open = false"
                    >
                        <button
                            type="button"
                            @click="toggle($event)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            :aria-expanded="open"
                            aria-haspopup="true"
                        >
                            Actions
                            <svg class="w-3.5 h-3.5 text-gray-500 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <template x-teleport="body">
                            <div
                                x-show="open"
                                x-cloak
                                x-transition
                                @click.outside="open = false"
                                :style="panelStyle"
                                class="w-44 rounded-lg border border-gray-200 dark-panel py-1 shadow-2xl text-left"
                                style="background: var(--theme-panel); color: var(--theme-text); z-index: 9999;"
                            >
                                <div class="px-3 py-2 border-b border-gray-100">
                                    <p class="text-[10px] font-mono text-gray-500">{{ $application->reference_no }}</p>
                                </div>
                                <a href="{{ route('spes.show', $application) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" @click="open = false">View</a>
                                @if(auth()->user()?->canAccess('spr.edit'))
                                <a href="{{ route('spes.edit', $application) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" @click="open = false">Edit</a>
                                @endif
                                <a href="{{ route('spes.print', $application) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" target="_blank" @click="open = false">Print</a>
                                <a href="{{ route('spes.pdf', $application) }}" class="block px-3 py-2 text-xs font-medium ui-link hover:bg-gray-50" @click="open = false">PDF</a>
                            </div>
                        </template>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 py-10">No SPR forms yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $applications->links() }}</div>
@endsection
