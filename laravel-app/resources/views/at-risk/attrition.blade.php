@extends('layouts.app')
@section('title', 'Attrition estimate')
@section('hidePageHead', '1')

@section('content')
<style>
    .risk-kicker {
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--theme-muted);
    }
    .risk-note { margin-top: 0.35rem; font-size: 0.8125rem; line-height: 1.45; color: var(--theme-muted); }
    .risk-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        font-size: 0.75rem;
        font-weight: 600;
        background: color-mix(in srgb, var(--theme-bg) 55%, var(--theme-panel));
        color: var(--theme-text);
        border: 1px solid var(--theme-border);
    }
</style>

<div class="ui-panel mb-6 overflow-hidden">
    <div class="px-5 pt-5 pb-4 sm:px-6 border-b border-gray-100" style="background: linear-gradient(180deg, color-mix(in srgb, #0f766e 9%, var(--theme-panel)), var(--theme-panel));">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <a href="{{ route('at-risk.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-teal-800">At-risk students</a>
                <h3 class="ui-page-title mt-1">Random Forest attrition estimate</h3>
                <p class="ui-page-sub">This is separate from the yearly enrollment forecast. It looks at grade, failed subjects, year level, and age. It learns from students already marked dropped, withdrawn, or stopped, then flags enrolled students who look similar. The status field is the answer, not an input.</p>
            </div>
            <form method="POST" action="{{ route('at-risk.attrition') }}" class="shrink-0">
                @csrf
                <button type="submit" class="ui-btn-primary">Run student model</button>
            </form>
        </div>
    </div>
    <div class="p-5 sm:p-6">
        @if(empty($attrition))
            <p class="text-sm text-gray-600">No estimate yet. Run the student model to fill this page.</p>
        @elseif(!($attrition['ok'] ?? false))
            <p class="text-sm text-amber-800">{{ $attrition['message'] }}</p>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-xl border px-4 py-3" style="border-color: var(--theme-border);">
                    <p class="ui-kpi-label">Records checked</p>
                    <p class="mt-1 text-xl font-bold">{{ number_format($attrition['examined']) }}</p>
                </div>
                <div class="rounded-xl border px-4 py-3" style="border-color: var(--theme-border);">
                    <p class="ui-kpi-label">Already left</p>
                    <p class="mt-1 text-xl font-bold">{{ number_format($attrition['left']) }}</p>
                </div>
                <div class="rounded-xl border px-4 py-3" style="border-color: var(--theme-border);">
                    <p class="ui-kpi-label">Still enrolled</p>
                    <p class="mt-1 text-xl font-bold">{{ number_format($attrition['stayed']) }}</p>
                </div>
                <div class="rounded-xl border px-4 py-3" style="border-color: var(--theme-border); border-left: 3px solid #b45309;">
                    <p class="ui-kpi-label">Flagged at 50%+</p>
                    <p class="mt-1 text-xl font-bold text-amber-800">{{ number_format($attrition['flagged']) }}</p>
                    <p class="risk-note">Hold-out accuracy {{ $attrition['accuracy'] }}% · caught {{ $attrition['caught'] }}%</p>
                </div>
            </div>
            @if($attrition['students'] === [])
                <p class="mt-4 text-sm text-gray-500">No enrolled student reached a 50% estimate.</p>
            @else
                <p class="risk-kicker mt-6">Flagged students</p>
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th class="text-left">Student ID</th>
                                <th class="text-left">Estimate</th>
                                <th class="text-right">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attrition['students'] as $row)
                                <tr>
                                    <td class="font-mono text-sm font-semibold">{{ $row['student_id'] }}</td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $row['chance'] }}%</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('at-risk.attrition.student', ['studentId' => $row['student_id']]) }}" class="ui-link text-sm">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
