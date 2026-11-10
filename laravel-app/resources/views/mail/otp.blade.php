<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f5; padding:24px;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:12px; padding:28px; color:#1f2937; border-top:4px solid #0b5d3b;">
        <p style="margin:0 0 4px; font-size:12px; letter-spacing:1px; color:#d4a017; font-weight:bold;">CSUCC</p>
        <h2 style="margin:0 0 4px; color:#0b5d3b;">Caraga State University Cabadbaran Campus</h2>
        <p style="margin:0 0 16px; color:#6b7280; font-size:14px;">Your Verification Code</p>
        <p style="margin:0 0 12px; font-size:14px;">Your verification code is:</p>
        <p style="font-size:32px; letter-spacing:8px; font-weight:bold; text-align:center; background:#ecfdf5; padding:16px; border-radius:8px; color:#065f46;">{{ $code }}</p>
        <p style="margin:16px 0 0; font-size:14px;">This code will expire in {{ $minutes }} minutes.</p>
        <p style="margin:16px 0 0; font-size:12px; color:#9ca3af;">If you did not request this code, you can ignore this message.</p>
        <p style="margin:12px 0 0; font-size:11px; color:#9ca3af;">Purpose: {{ $purposeLabel }}</p>
    </div>
</body>
</html>
