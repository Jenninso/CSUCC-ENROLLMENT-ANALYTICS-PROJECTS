<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $application->reference_no }} — Student Personal Record</title>
    <style>
        @page { size: 8.5in 13in; margin: 0; }
        html, body { margin: 0; background: {{ !empty($forPdf) ? '#fff' : '#e7e5e4' }}; }
        .toolbar { width: 8.5in; margin: 12px auto; font-family: system-ui, sans-serif; }
        .toolbar button, .toolbar a { font-size: 13px; margin-right: 8px; }
        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    @empty($forPdf)
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ !empty($studentMode) ? route('student.spes.pdf', $application) : route('spes.pdf', $application) }}">Download PDF</a>
        <a href="{{ !empty($studentMode) ? route('student.spes.show', $application) : route('spes.show', $application) }}">Back to record</a>
        <span style="font-size: 12px; color: #444;">Long bond paper, 8.5 × 13 in, one page</span>
    </div>
    @endempty
    @include('spes.paper', ['editing' => false])
</body>
</html>
