<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SPR QR code</title>
    <style>
        html, body { margin: 0; background: #e7e5e4; color: #111; font-family: system-ui, sans-serif; }
        .toolbar { max-width: 8.5in; margin: 16px auto; }
        .toolbar button, .toolbar a { font-size: 14px; margin-right: 8px; }
        .sheet {
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto 24px;
            background: #fff;
            box-sizing: border-box;
            padding: 0.8in 0.7in;
            text-align: center;
        }
        h1 { font-size: 22px; margin: 0 0 6px; }
        h2 { font-size: 16px; font-weight: 600; margin: 0 0 18px; }
        .qr { width: 2.8in; height: 2.8in; }
        .url { margin-top: 14px; font-size: 13px; word-break: break-all; }
        .note { margin-top: 16px; font-size: 14px; line-height: 1.4; }
        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; min-height: auto; }
        }
        @page { size: letter; margin: 0.4in; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('spes.index') }}">Back to SPR</a>
    </div>
    <section class="sheet">
        <h1>Caraga State University Cabadbaran City</h1>
        <h2>Student Personal Record (SPR)</h2>
        <img src="{{ $shareQr }}" alt="QR code for the student SPR form" class="qr" width="280" height="280">
        <p class="url">{{ $shareUrl }}</p>
        <p class="note">Scan this code to open the blank SPR form. Students do not sign in. They fill the form and save it.</p>
    </section>
</body>
</html>
