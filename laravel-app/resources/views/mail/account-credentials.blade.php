<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f5; padding:24px;">
    <div style="max-width:520px; margin:0 auto; background:#ffffff; border-radius:12px; padding:28px; color:#1f2937; border-top:4px solid #0b5d3b;">
        <p style="margin:0 0 4px; font-size:12px; letter-spacing:1px; color:#d4a017; font-weight:bold;">CSUCC</p>
        <h2 style="margin:0 0 4px; color:#0b5d3b;">Caraga State University Cabadbaran Campus</h2>
        <p style="margin:0 0 16px; color:#6b7280; font-size:14px;">Account Role Assignment</p>

        <p style="margin:0 0 12px; font-size:14px; line-height:1.5;">
            Your account has been turned into <strong style="color:#0b5d3b;">{{ $roleName }}</strong>.
            Use the sign-in details below. You must change the temporary password on first login.
        </p>

        <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:14px;">
            <tr>
                <td style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; width:40%;">Employee ID</td>
                <td style="padding:10px 12px; background:#ffffff; border:1px solid #e2e8f0; font-family:monospace;">{{ $employeeId }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b;">Role</td>
                <td style="padding:10px 12px; background:#ffffff; border:1px solid #e2e8f0; font-weight:600;">{{ $roleName }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b;">Username</td>
                <td style="padding:10px 12px; background:#ffffff; border:1px solid #e2e8f0; font-family:monospace; font-weight:600;">{{ $username }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b;">Email (OTP / notices)</td>
                <td style="padding:10px 12px; background:#ffffff; border:1px solid #e2e8f0; word-break:break-all;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b;">Temporary password</td>
                <td style="padding:10px 12px; background:#ecfdf5; border:1px solid #e2e8f0; font-family:monospace; font-weight:bold; color:#065f46; letter-spacing:0.5px;">{{ $temporaryPassword }}</td>
            </tr>
        </table>

        <p style="margin:16px 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
            Sign in with this <strong>username</strong> and temporary password. OTP codes and account messages are sent to the email above.
        </p>
        <p style="margin:12px 0 0; font-size:12px; color:#9ca3af;">
            If you did not expect this message, contact a Super Administrator or Administrator.
        </p>
    </div>
</body>
</html>
