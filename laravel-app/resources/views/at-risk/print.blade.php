<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>At-risk report {{ $student->student_id }}</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin: 18px 0 6px; }
        p, li, td { font-size: 13px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ccc; text-align: left; padding: 4px 6px; }
        .muted { color: #444; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <p class="muted">Caraga State University Cabadbaran Campus · Student At-Risk Analysis Report</p>
    <h1>{{ $student->student_id }}</h1>
    <p>{{ $analysis['name'] ?: 'No SPR name' }} · {{ $analysis['program_name'] }} · {{ $analysis['year_label'] }} {{ $analysis['section'] }} · {{ $analysis['term'] }}</p>
    <p><strong>Risk classification:</strong> {{ $analysis['level_label'] }}</p>

    <h2>System analysis</h2>
    <p>{{ $analysis['narrative'] }}</p>

    <h2>Academic history</h2>
    <table>
        <thead>
            <tr><th>Term</th><th>GPA</th><th>Average</th><th>Attendance</th><th>Failed</th><th>Standing</th></tr>
        </thead>
        <tbody>
            @foreach($analysis['history'] as $term)
            <tr>
                <td>{{ $term['term'] }}</td>
                <td>{{ number_format($term['gpa'], 2) }}</td>
                <td>{{ number_format($term['average_grade'], 2) }}</td>
                <td>{{ number_format($term['attendance'], 1) }}%</td>
                <td>{{ $term['failed_subjects'] }}</td>
                <td>{{ $term['enrolled'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>SPR background</h2>
    @if($analysis['spr'])
        <p>Household: {{ $analysis['spr']['household'] }}. Solo parent: {{ $analysis['spr']['solo_parent'] }}. Working student: {{ $analysis['spr']['working'] }}. 4Ps: {{ $analysis['spr']['four_ps'] }}.</p>
        <p>Income: {{ $analysis['spr']['income'] }} monthly, ₱{{ $analysis['spr']['annual_income'] }} estimated annual.</p>
        <p>Father: {{ $analysis['spr']['father'] }}</p>
        <p>Mother: {{ $analysis['spr']['mother'] }}</p>
    @else
        <p>No SPR is linked to this student ID.</p>
    @endif

    <h2>Academic indicators</h2>
    <ul>@foreach($analysis['indicators'] as $item)<li>{{ $item }}</li>@endforeach</ul>

    <h2>Possible contributing circumstances</h2>
    @if($analysis['factors'] === [])
        <p>None recorded.</p>
    @else
        <ul>@foreach($analysis['factors'] as $item)<li>{{ $item }}</li>@endforeach</ul>
    @endif

    <h2>Recommended support</h2>
    <ul>@foreach($analysis['recommendations'] as $item)<li><strong>{{ $item['title'] }}.</strong> {{ $item['detail'] }}</li>@endforeach</ul>

    <h2>Interventions on file</h2>
    @forelse($interventions as $intervention)
        <p>{{ $intervention->provided_on?->format('M j, Y') }} — {{ $intervention->typeLabel() }} ({{ $intervention->outcomeLabel() }}). GPA at the time {{ number_format((float) $intervention->gpa_at_record, 2) }}, attendance {{ number_format((float) $intervention->attendance_at_record, 1) }}%. {{ $intervention->notes }}</p>
    @empty
        <p>None recorded.</p>
    @endforelse

    <p class="muted">Family and financial details are for follow-up only. They did not set the risk classification. Estimated absences use {{ \App\Services\StudentRiskAnalysis::TERM_MEETINGS }} class meetings. Tardiness is not stored.</p>
</body>
</html>
