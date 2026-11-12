@php
    $badge = \App\Models\StudentRecord::query()->make(['risk_level' => $analysis['level']])->riskBadgeClass();
@endphp

<div class="ui-panel p-4 mb-4 text-sm text-gray-700 leading-relaxed">
    This report is for authorized school staff and for educational support only. Family structure and income are possible circumstances. They are not used to classify the student, and they should be checked with the student or a guardian before action.
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="ui-kpi">
        <p class="ui-kpi-label">Risk level</p>
        <p class="mt-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ $analysis['level_label'] }}</span></p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Current GPA</p>
        <p class="ui-kpi-value text-xl">{{ number_format($analysis['gpa'], 2) }}</p>
        @if($analysis['previous_gpa'] !== null)
            <p class="text-xs text-gray-400 mt-1">Previous {{ number_format($analysis['previous_gpa'], 2) }}</p>
        @endif
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Attendance</p>
        <p class="ui-kpi-value text-xl">{{ number_format($analysis['attendance'], 1) }}%</p>
        <p class="text-xs text-gray-400 mt-1">About {{ $analysis['estimated_absences'] }} absences</p>
    </div>
    <div class="ui-kpi">
        <p class="ui-kpi-label">Failed subjects</p>
        <p class="ui-kpi-value text-xl">{{ $analysis['failed_subjects'] }}</p>
        <p class="text-xs text-gray-400 mt-1">of {{ $analysis['subjects_count'] }}</p>
    </div>
</div>

<div class="ui-panel p-5 mb-4">
    <h3 class="text-base font-semibold text-gray-800 mb-2">System analysis</h3>
    <p class="text-sm text-gray-700 leading-relaxed">{{ $analysis['narrative'] }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Academic indicators</h3>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-2">
            @foreach($analysis['indicators'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="text-xs text-gray-500 mt-3">Absences are estimated from attendance using {{ \App\Services\StudentRiskAnalysis::TERM_MEETINGS }} class meetings. The academic file does not store tardiness or subject-by-subject grades.</p>
    </div>
    <div class="ui-panel p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Possible contributing circumstances</h3>
        @if($analysis['factors'] === [])
            <p class="text-sm text-gray-600">None recorded. A low income or a particular household is not treated as an academic risk by itself.</p>
        @else
            <ul class="list-disc pl-5 text-sm text-gray-700 space-y-2">
                @foreach($analysis['factors'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="ui-panel p-5 mb-4">
    <h3 class="text-base font-semibold text-gray-800 mb-3">Academic history</h3>
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr>
                    <th class="text-left">Term</th>
                    <th class="text-left">GPA</th>
                    <th class="text-left">Average</th>
                    <th class="text-left">Attendance</th>
                    <th class="text-left">Failed</th>
                    <th class="text-left">Standing</th>
                    <th class="text-left">Term risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analysis['history'] as $term)
                <tr>
                    <td>{{ $term['term'] }}</td>
                    <td class="tabular-nums">{{ number_format($term['gpa'], 2) }}</td>
                    <td class="tabular-nums">{{ number_format($term['average_grade'], 2) }}</td>
                    <td class="tabular-nums">{{ number_format($term['attendance'], 1) }}%</td>
                    <td>{{ $term['failed_subjects'] }}</td>
                    <td>{{ $term['enrolled'] }}</td>
                    <td>{{ \App\Models\StudentRecord::RISK_LEVELS[$term['level']] ?? $term['level'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="ui-panel p-5 mb-4">
    <h3 class="text-base font-semibold text-gray-800 mb-3">SPR family and financial background</h3>
    @if($analysis['spr'])
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="ui-kpi-label mb-1">Household</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['household'] }}</dd></div>
            <div><dt class="ui-kpi-label mb-1">Solo parent</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['solo_parent'] }}</dd></div>
            <div><dt class="ui-kpi-label mb-1">Working student</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['working'] }}</dd></div>
            <div><dt class="ui-kpi-label mb-1">4Ps</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['four_ps'] }}</dd></div>
            <div><dt class="ui-kpi-label mb-1">Estimated monthly income</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['income'] }}</dd></div>
            <div><dt class="ui-kpi-label mb-1">Estimated annual income</dt><dd class="font-semibold text-gray-900">₱{{ $analysis['spr']['annual_income'] }}</dd></div>
            <div class="sm:col-span-2"><dt class="ui-kpi-label mb-1">Father</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['father'] }}</dd></div>
            <div class="sm:col-span-2"><dt class="ui-kpi-label mb-1">Mother</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['mother'] }}</dd></div>
            <div class="sm:col-span-2"><dt class="ui-kpi-label mb-1">Home address</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['address'] ?: '—' }}</dd></div>
        </dl>
    @else
        <p class="text-sm text-gray-600">No SPR is linked to this student ID. Academic classification still stands. Family and income context will appear after an SPR is filed.</p>
    @endif
</div>

<div class="ui-panel p-5 mb-4">
    <h3 class="text-base font-semibold text-gray-800 mb-3">Recommended support</h3>
    <ul class="space-y-3">
        @foreach($analysis['recommendations'] as $item)
        <li>
            <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
            <p class="text-sm text-gray-600 mt-0.5">{{ $item['detail'] }}</p>
        </li>
        @endforeach
    </ul>
</div>
