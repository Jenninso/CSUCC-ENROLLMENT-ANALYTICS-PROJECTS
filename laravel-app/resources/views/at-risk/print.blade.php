<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>At-risk report {{ $student->student_id }}</title>
    <style>
        :root {
            --teal: #0f766e;
            --teal-dark: #115e59;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --panel: #f8fafc;
            --good: #065f46;
            --good-bg: #d1fae5;
            --warn: #92400e;
            --warn-bg: #fef3c7;
            --alert: #9a3412;
            --alert-bg: #ffedd5;
            --bad: #991b1b;
            --bad-bg: #fee2e2;
            --sky: #075985;
            --sky-bg: #e0f2fe;
        }
        * { box-sizing: border-box; }
        /* Long bond paper (Philippine Folio): 8.5" × 13" */
        @page {
            size: 8.5in 13in;
            margin: 0.45in 0.5in 0.5in 0.5in;
        }
        body {
            font-family: "Segoe UI", Calibri, Arial, sans-serif;
            color: var(--ink);
            margin: 0 auto;
            padding: 18px 22px 28px;
            max-width: 8.5in;
            font-size: 11.5px;
            line-height: 1.4;
            background: #fff;
        }
        .toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .toolbar button, .toolbar a {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid var(--line);
            cursor: pointer;
            text-decoration: none;
            color: var(--ink);
            background: #fff;
        }
        .toolbar .primary {
            background: linear-gradient(180deg, #14b8a6, var(--teal));
            color: #fff;
            border-color: var(--teal-dark);
        }
        .doc-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            padding: 16px 18px;
            border: 1px solid color-mix(in srgb, var(--teal) 35%, var(--line));
            border-left: 5px solid var(--teal);
            border-radius: 10px;
            background: linear-gradient(135deg, #ecfdf5 0%, #fff 55%);
            margin-bottom: 14px;
        }
        .kicker {
            margin: 0 0 4px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--teal);
        }
        h1 {
            margin: 0;
            font-size: 24px;
            font-family: ui-monospace, Consolas, monospace;
            letter-spacing: -0.02em;
        }
        .meta {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12.5px;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .badge-low { background: var(--good-bg); color: var(--good); border-color: #a7f3d0; }
        .badge-moderate { background: var(--warn-bg); color: var(--warn); border-color: #fde68a; }
        .badge-high { background: var(--alert-bg); color: var(--alert); border-color: #fed7aa; }
        .badge-critical { background: var(--bad-bg); color: var(--bad); border-color: #fecaca; }
        .snapshot {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .snap {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            border-left: 4px solid var(--teal);
            background: var(--panel);
        }
        .snap.good { border-left-color: var(--good); background: #ecfdf5; }
        .snap.warn { border-left-color: #d97706; background: #fffbeb; }
        .snap.alert { border-left-color: #ea580c; background: #fff7ed; }
        .snap.bad { border-left-color: #b91c1c; background: #fef2f2; }
        .snap.sky { border-left-color: #0284c7; background: #f0f9ff; }
        .snap-label {
            margin: 0;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .snap-value {
            margin: 4px 0 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .snap-sub { margin: 2px 0 0; color: var(--muted); font-size: 11px; }
        .section {
            border: 1px solid var(--line);
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .section-head {
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #ecfdf5;
            background: linear-gradient(180deg, #0d9488, var(--teal));
        }
        .section-head.warn { background: linear-gradient(180deg, #f59e0b, #d97706); color: #fffbeb; }
        .section-head.sky { background: linear-gradient(180deg, #0ea5e9, #0369a1); color: #f0f9ff; }
        .section-head.alert { background: linear-gradient(180deg, #fb923c, #ea580c); color: #fff7ed; }
        .section-body { padding: 12px 14px; }
        .section-body p { margin: 0 0 8px; }
        .section-body p:last-child { margin-bottom: 0; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #ecfdf5;
            background: linear-gradient(180deg, #0d9488, var(--teal));
            padding: 8px 8px;
            border-bottom: 1px solid var(--teal-dark);
        }
        td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .row-warn { background: #fffbeb !important; }
        .row-bad { background: #fef2f2 !important; font-weight: 700; }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.03em;
        }
        .pill-bad { background: var(--bad-bg); color: var(--bad); }
        .pill-alert { background: var(--alert-bg); color: var(--alert); }
        .pill-warn { background: var(--warn-bg); color: var(--warn); }
        .pill-good { background: var(--good-bg); color: var(--good); }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .spr-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
        .spr-item {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .spr-item strong {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--sky);
            margin-bottom: 2px;
        }
        ul { margin: 0; padding-left: 18px; }
        li { margin-bottom: 5px; }
        .support-item {
            border-left: 3px solid var(--teal);
            background: linear-gradient(90deg, #ecfdf5, #fff);
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .support-item:last-child { margin-bottom: 0; }
        .support-item strong { color: var(--teal-dark); }
        .note {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px dashed var(--line);
            color: var(--muted);
            font-size: 11px;
        }
        .muted { color: var(--muted); font-size: 11px; }
        @media print {
            html, body {
                width: 8.5in;
                max-width: 8.5in;
                padding: 0;
                margin: 0;
                font-size: 11px;
            }
            .no-print { display: none !important; }
            .section, .doc-head, .snap, .support-item { break-inside: avoid; }
            .snapshot { gap: 8px; margin-bottom: 10px; }
            .snap { padding: 8px 10px; }
            .snap-value { font-size: 17px; }
            .section { margin-bottom: 9px; }
            .section-body { padding: 9px 11px; }
            .doc-head { padding: 12px 14px; margin-bottom: 10px; }
            h1 { font-size: 20px; }
            th, td { padding: 5px 6px; }
            .two-col { gap: 9px; margin-bottom: 9px; }
            .note { margin-top: 10px; padding: 8px 10px; }
            a { color: inherit; text-decoration: none; }
        }
        @media (max-width: 720px) {
            .snapshot, .two-col, .spr-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
@php
    $level = $analysis['level'] ?? 'moderate';
    $badgeClass = match ($level) {
        'low' => 'badge-low',
        'moderate' => 'badge-moderate',
        'high' => 'badge-high',
        default => 'badge-critical',
    };
    $snapRisk = match ($level) {
        'low' => 'good',
        'moderate' => 'warn',
        'high' => 'alert',
        default => 'bad',
    };
    $gpaTone = ($analysis['gpa'] ?? 0) > 3 ? 'bad' : (($analysis['gpa'] ?? 0) >= 2.75 ? 'alert' : (($analysis['gpa'] ?? 0) >= 2.25 ? 'warn' : 'good'));
    $failedTone = ((int) ($analysis['failed_subjects'] ?? 0)) >= 3 ? 'bad' : (((int) ($analysis['failed_subjects'] ?? 0)) >= 1 ? 'alert' : 'good');
    $printAtRiskSubs = $analysis['subject_analysis']['at_risk_subjects']
        ?? array_values(array_filter($analysis['subject_analysis']['subjects'] ?? [], fn ($s) => $s['is_at_risk']));
@endphp

<div class="toolbar no-print">
    <button type="button" class="primary" onclick="window.print()">Print / Save PDF</button>
    <a href="{{ route('at-risk.show', $student) }}">← Back to report</a>
</div>

<header class="doc-head">
    <div>
        <p class="kicker">Caraga State University Cabadbaran Campus</p>
        <h1>{{ $student->student_id }}</h1>
        <p class="meta">
            {{ $analysis['program_name'] }}
            · {{ $analysis['year_label'] }} {{ $analysis['section'] }}
            · {{ $analysis['term'] }}
        </p>
        <p class="meta" style="margin-top: 2px;">Student At-Risk Analysis Report</p>
    </div>
    <div style="text-align: right;">
        <p class="kicker" style="margin-bottom: 6px;">Risk classification</p>
        <span class="badge {{ $badgeClass }}">{{ $analysis['level_label'] }}</span>
    </div>
</header>

<section class="snapshot">
    <div class="snap {{ $snapRisk }}">
        <p class="snap-label">Risk level</p>
        <p class="snap-value" style="font-size: 16px;">{{ $analysis['level_label'] }}</p>
    </div>
    <div class="snap {{ $gpaTone }}">
        <p class="snap-label">Current GPA</p>
        <p class="snap-value">{{ number_format($analysis['gpa'], 2) }}</p>
        @if(($analysis['previous_gpa'] ?? null) !== null)
            <p class="snap-sub">Previous {{ number_format($analysis['previous_gpa'], 2) }}</p>
        @endif
    </div>
    <div class="snap {{ $failedTone }}">
        <p class="snap-label">Failed subjects</p>
        <p class="snap-value">{{ $analysis['failed_subjects'] }}</p>
        <p class="snap-sub">of {{ $analysis['subjects_count'] }} enrolled</p>
    </div>
    <div class="snap sky">
        <p class="snap-label">Standing</p>
        <p class="snap-value" style="font-size: 15px;">{{ $student->academicStandingLabel() }}</p>
        <p class="snap-sub">{{ $student->studentTypeLabel() }} · {{ $student->enrolledLabel() }}</p>
    </div>
</section>

<section class="section">
    <div class="section-head">System analysis</div>
    <div class="section-body">
        <p>{{ $analysis['narrative'] }}</p>
    </div>
</section>

<section class="section">
    <div class="section-head">Academic history</div>
    <table>
        <thead>
            <tr>
                <th>Term</th>
                <th>GPA</th>
                <th>Average</th>
                <th>Failed</th>
                <th>Standing</th>
                <th>Term risk</th>
            </tr>
        </thead>
        <tbody>
            @foreach($analysis['history'] as $term)
            @php
                $termLevel = $term['level'] ?? 'low';
                $termPill = match ($termLevel) {
                    'low' => 'pill-good',
                    'moderate' => 'pill-warn',
                    'high' => 'pill-alert',
                    default => 'pill-bad',
                };
            @endphp
            <tr>
                <td>{{ $term['term'] }}</td>
                <td>{{ number_format($term['gpa'], 2) }}</td>
                <td>{{ number_format($term['average_grade'], 2) }}</td>
                <td>{{ $term['failed_subjects'] }}</td>
                <td>{{ $term['enrolled'] }}</td>
                <td><span class="pill {{ $termPill }}">{{ \App\Models\StudentRecord::RISK_LEVELS[$termLevel] ?? $termLevel }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</section>

@if(!empty($printAtRiskSubs))
<section class="section">
    <div class="section-head alert">At-risk & critical subject difficulties</div>
    <div class="section-body" style="padding-bottom: 8px;">
        @if(($analysis['subject_analysis']['summary']['failed_count'] ?? 0) > 0)
            <p style="color: var(--bad); font-weight: 700; margin-bottom: 8px;">
                Critical / failing: {{ $analysis['subject_analysis']['summary']['struggling_subjects_text'] }}
            </p>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Category</th>
                <th>Units</th>
                <th>Difficulty</th>
                <th>Grade</th>
                <th>Status</th>
                <th>Recommendation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($printAtRiskSubs as $sub)
            <tr class="{{ $sub['is_failed'] ? 'row-bad' : 'row-warn' }}">
                <td><strong>{{ $sub['code'] }}</strong><br><span class="muted">{{ $sub['title'] }}</span></td>
                <td>{{ $sub['category'] }}</td>
                <td>{{ number_format($sub['units'], 1) }}</td>
                <td>{{ $sub['difficulty_label'] }} ({{ $sub['difficulty_pct'] }}%)</td>
                <td>{{ $sub['grade'] }}</td>
                <td>
                    <span class="pill {{ $sub['is_failed'] ? 'pill-bad' : 'pill-alert' }}">
                        {{ $sub['is_failed'] ? 'Critical / Failed' : 'High risk' }}
                    </span>
                </td>
                <td>{{ $sub['recommendation'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="section-body">
        <p class="muted">* {{ count($analysis['subject_analysis']['good_subjects'] ?? []) }} subject(s) in good standing are omitted from this referral list.</p>
    </div>
</section>
@else
<section class="section">
    <div class="section-head">Subject diagnostics</div>
    <div class="section-body">
        <p>All enrolled subjects for this term are in good academic standing (0 subjects at risk).</p>
    </div>
</section>
@endif

<section class="section">
    <div class="section-head sky">SPR background</div>
    <div class="section-body">
        @if($analysis['spr'])
            <div class="spr-grid">
                <div class="spr-item"><strong>Household</strong>{{ $analysis['spr']['household'] }}</div>
                <div class="spr-item"><strong>Solo parent</strong>{{ $analysis['spr']['solo_parent'] }}</div>
                <div class="spr-item"><strong>Working student</strong>{{ $analysis['spr']['working'] }}</div>
                <div class="spr-item"><strong>4Ps</strong>{{ $analysis['spr']['four_ps'] }}</div>
                <div class="spr-item"><strong>Monthly income</strong>{{ $analysis['spr']['income'] }}</div>
                <div class="spr-item"><strong>Est. annual</strong>₱{{ $analysis['spr']['annual_income'] }}</div>
                <div class="spr-item" style="grid-column: span 1;"><strong>Father's occupation</strong>{{ $analysis['spr']['father'] }}</div>
                <div class="spr-item" style="grid-column: span 2;"><strong>Mother's occupation</strong>{{ $analysis['spr']['mother'] }}</div>
            </div>
        @else
            <p>No SPR is linked to this student ID.</p>
        @endif
    </div>
</section>

<div class="two-col">
    <section class="section" style="margin-bottom: 0;">
        <div class="section-head warn">Academic indicators</div>
        <div class="section-body">
            <ul>
                @foreach($analysis['indicators'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>
    <section class="section" style="margin-bottom: 0;">
        <div class="section-head sky">Possible contributing circumstances</div>
        <div class="section-body">
            @if($analysis['factors'] === [])
                <p>None recorded.</p>
            @else
                <ul>
                    @foreach($analysis['factors'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>

<section class="section" style="margin-top: 12px;">
    <div class="section-head">Recommended support</div>
    <div class="section-body">
        @foreach($analysis['recommendations'] as $item)
            <div class="support-item">
                <strong>{{ $item['title'] }}.</strong> {{ $item['detail'] }}
            </div>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-head">Interventions on file</div>
    <div class="section-body">
        @forelse($interventions as $intervention)
            <div class="support-item" style="border-left-color: #0369a1; background: linear-gradient(90deg, #e0f2fe, #fff);">
                <strong>{{ $intervention->provided_on?->format('M j, Y') }} — {{ $intervention->typeLabel() }}</strong>
                ({{ $intervention->outcomeLabel() }}). GPA at the time {{ number_format((float) $intervention->gpa_at_record, 2) }}.
                {{ $intervention->notes }}
            </div>
        @empty
            <p>None recorded.</p>
        @endforelse
    </div>
</section>

<p class="note">
    Family and financial details are for follow-up only. They did not set the risk classification.
    Estimated absences use {{ \App\Services\StudentRiskAnalysis::TERM_MEETINGS }} class meetings. Tardiness is not stored.
    Generated for authorized CEIT / CSUCC staff use.
</p>
</body>
</html>
