<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Personal Record</title>
    <style>
        body { margin: 0; background: #f3f4f6; font-family: system-ui, sans-serif; color: #111; }
        .bar { max-width: 8.5in; margin: 0 auto; padding: 16px 12px 0; }
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
