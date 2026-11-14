<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Personal Record</title>
    <style>
        body { margin: 0; background: #f4f6f5; font-family: system-ui, sans-serif; color: #111; }
        .bar { max-width: 8.5in; margin: 0 auto; padding: 20px 16px 0; }
        .spr-top {
            background: #fff;
            border: 1px solid #e6ebe8;
            border-radius: 14px;
            padding: 18px 18px 16px;
            box-shadow: 0 1px 2px rgba(11, 93, 59, 0.04);
        }
        .spr-top h1 {
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #0b5d3b;
        }
        .spr-top .lead {
            margin: 6px 0 0;
            color: #5b6560;
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .spr-notes {
            margin: 14px 0 0;
            padding: 10px 12px;
            list-style: none;
            background: #f7faf8;
            border-radius: 10px;
        }
        .spr-notes li {
            position: relative;
            margin: 0;
            padding: 6px 0 6px 16px;
            color: #24312c;
            font-size: 0.88rem;
            line-height: 1.4;
            font-weight: 500;
        }
        .spr-notes li + li { border-top: 1px solid #e7eeea; }
        .spr-notes li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 12px;
            width: 6px;
            height: 6px;
            border-radius: 99px;
            background: #0b5d3b;
        }
        .spr-flash { margin-top: 10px; border-radius: 10px; padding: 10px 12px; font-size: 0.9rem; }
        .spr-flash.ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #064e3b; }
        .spr-flash.warn { background: #fffbeb; border: 1px solid #fde68a; color: #78350f; }
        .spr-flash.bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .sheet-guard { max-width: 100%; }
        .spr-sheet, .spr-sheet * { -webkit-user-select: none; user-select: none; }
        .spr-sheet input, .spr-sheet textarea, .spr-sheet select { -webkit-user-select: text; user-select: text; }
        .spr-head img { -webkit-user-drag: none; pointer-events: none; }
        @media print {
            body * { display: none !important; }
            body::after { content: "Printing this form is not allowed."; display: block !important; padding: 2rem; font: 16px system-ui, sans-serif; }
        }
    </style>
</head>
<body oncontextmenu="return false">
    @yield('content')
    <script>
        document.addEventListener('keydown', function (event) {
            const key = (event.key || '').toLowerCase();
            const blocked = key === 'printscreen'
                || (event.ctrlKey && ['p', 's', 'u'].includes(key))
                || (event.ctrlKey && event.shiftKey && ['i', 'c', 'j'].includes(key))
                || (event.metaKey && ['p', 's'].includes(key));
            if (blocked) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
        document.addEventListener('keyup', function (event) {
            if ((event.key || '').toLowerCase() === 'printscreen') {
                event.preventDefault();
            }
        });
        document.addEventListener('dragstart', function (event) {
            if (event.target && event.target.tagName === 'IMG') {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
