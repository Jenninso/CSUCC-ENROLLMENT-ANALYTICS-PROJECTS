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

@php
    $subAnalysis = $analysis['subject_analysis'] ?? null;
    $subjects = $subAnalysis['subjects'] ?? [];
    $subSummary = $subAnalysis['summary'] ?? [];
    $cohortHeatmap = $subAnalysis['cohort_heatmap'] ?? [];
@endphp

@if(!empty($subjects))
<div class="ui-panel p-5 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-800">Diagnostics</span>
                <h3 class="text-base font-semibold text-gray-900">Subject Difficulties & Performance Heatmap</h3>
            </div>
            <p class="text-xs text-gray-500 mt-1">Individual subject risk profile, historical difficulty rating, and course standing for {{ $analysis['program'] }} {{ $analysis['year_label'] }} ({{ $analysis['term'] }}).</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-200">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span> Failed ({{ $subSummary['failed_count'] ?? 0 }})
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-200">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Warning ({{ $subSummary['warning_count'] ?? 0 }})
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-200">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Passed ({{ $subSummary['passed_count'] ?? 0 }})
            </span>
        </div>
    </div>

    @if(($subSummary['failed_count'] ?? 0) > 0)
    <div class="mb-4 p-3.5 rounded-lg bg-red-50/90 border border-red-200 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div class="text-xs text-red-900 leading-relaxed">
            <strong class="font-semibold">Critical Subject Alert:</strong> Student is struggling and currently failing in <strong>{{ $subSummary['failed_count'] }} subject{{ ($subSummary['failed_count'] > 1) ? 's' : '' }}</strong>:
            <span class="font-medium underline">{{ $subSummary['struggling_subjects_text'] }}</span>.
            Priority action: Schedule remedial assistance and review prerequisites.
        </div>
    </div>
    @endif

    {{-- Subject Heatmap Table --}}
    <div class="overflow-x-auto mb-5">
        <table class="ui-table">
            <thead>
                <tr>
                    <th class="text-left">Subject Code & Title</th>
                    <th class="text-left">Category</th>
                    <th class="text-center">Units</th>
                    <th class="text-left">Subject Difficulty Index</th>
                    <th class="text-center">Grade Standing</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Faculty Advising Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjects as $sub)
                <tr style="{{ $sub['is_failed'] ? 'background-color: #fef2f2;' : ($sub['is_warning'] ? 'background-color: #fffbeb;' : '') }}">
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-xs px-2 py-0.5 rounded {{ $sub['is_failed'] ? 'bg-red-200 text-red-900 ring-1 ring-red-300' : ($sub['is_warning'] ? 'bg-amber-200 text-amber-900' : 'bg-gray-100 text-gray-800') }}">
                                {{ $sub['code'] }}
                            </span>
                            <span class="font-semibold text-gray-900 text-xs">{{ $sub['title'] }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="text-xs text-gray-600">{{ $sub['category'] }}</span>
                    </td>
                    <td class="text-center font-mono text-xs">
                        {{ number_format($sub['units'], 1) }}
                    </td>
                    <td>
                        <div class="w-40">
                            <div class="flex justify-between items-center text-[11px] mb-1">
                                <span class="font-semibold" style="color: {{ $sub['difficulty_color'] }};">{{ $sub['difficulty_label'] }}</span>
                                <span class="text-gray-500 font-mono">{{ $sub['difficulty_pct'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $sub['difficulty_pct'] }}%; background-color: {{ $sub['difficulty_color'] }};"></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="font-mono font-bold text-xs px-2.5 py-1 rounded {{ $sub['is_failed'] ? 'bg-red-600 text-white' : ($sub['is_warning'] ? 'bg-amber-500 text-white' : 'bg-emerald-100 text-emerald-800') }}">
                            {{ $sub['grade'] }}
                        </span>
                    </td>
                    <td>
                        @if($sub['is_failed'])
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-red-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                FAILED
                            </span>
                        @elseif($sub['is_warning'])
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                AT RISK
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                PASSED
                            </span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-600 max-w-xs">
                        {{ $sub['recommendation'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Benchmark Heatmap: Program x Year Level --}}
    @if(!empty($cohortHeatmap['rows']))
    <div class="pt-4 border-t border-gray-100">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700">CEIT Cohort Difficulty Benchmark (Course × Year Level)</h4>
                <p class="text-[11px] text-gray-500">Darker cells indicate higher average subject failures across the college. Compare {{ $analysis['program'] }} {{ $analysis['year_label'] }} against other levels.</p>
            </div>
            <div class="flex items-center gap-1 text-[11px] text-gray-500">
                <span>Low Risk</span>
                <span class="inline-block w-3.5 h-3.5 rounded" style="background: #ecfdf5; border: 1px solid #a7f3d0;"></span>
                <span class="inline-block w-3.5 h-3.5 rounded" style="background: #fee2e2; border: 1px solid #fca5a5;"></span>
                <span class="inline-block w-3.5 h-3.5 rounded" style="background: #b91c1c;"></span>
                <span>Critical Risk</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-1.5 pr-3 font-semibold">Course</th>
                        @foreach(($cohortHeatmap['levels'] ?? [1, 2, 3, 4]) as $lvl)
                            <th class="py-1.5 px-2 font-semibold {{ (int)$analysis['year_level'] === $lvl ? 'text-indigo-700 bg-indigo-50/50 rounded-t' : '' }}">
                                Year {{ $lvl }} {{ (int)$analysis['year_level'] === $lvl ? '★' : '' }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(($cohortHeatmap['rows'] ?? []) as $cRow)
                    @php $isStudentProgram = ($cRow['program'] === $analysis['program']); @endphp
                    <tr class="{{ $isStudentProgram ? 'bg-indigo-50/30 font-semibold' : '' }}">
                        <td class="py-1.5 pr-3 text-gray-800">
                            {{ $cRow['program'] }} {{ $isStudentProgram ? '(Student Course)' : '' }}
                        </td>
                        @foreach(($cohortHeatmap['levels'] ?? [1, 2, 3, 4]) as $lvl)
                            @php
                                $cCell = $cRow['cells'][$lvl] ?? null;
                                $failedAvg = $cCell['failed'] ?? 0;
                                $cMax = max(0.01, (float)($cohortHeatmap['max'] ?? 1));
                                $cMix = (int) round(($failedAvg / $cMax) * 75);
                                $isCurrentCohort = ($isStudentProgram && (int)$analysis['year_level'] === $lvl);
                            @endphp
                            <td class="py-1 px-2">
                                <span class="inline-flex min-w-16 justify-center rounded px-2 py-0.5 text-xs font-semibold {{ $isCurrentCohort ? 'ring-2 ring-indigo-500 shadow-sm' : '' }}"
                                      style="background: color-mix(in srgb, #b91c1c {{ $cMix }}%, #ecfdf5); color: {{ $cMix > 45 ? '#ffffff' : '#1f2937' }};"
                                      title="{{ $cCell['students'] ?? 0 }} enrolled students">
                                    {{ $cCell ? number_format($failedAvg, 2) : '—' }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

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
            <div class="sm:col-span-2"><dt class="ui-kpi-label mb-1">Father's occupation</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['father'] }}</dd></div>
            <div class="sm:col-span-2"><dt class="ui-kpi-label mb-1">Mother's occupation</dt><dd class="font-semibold text-gray-900">{{ $analysis['spr']['mother'] }}</dd></div>
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
