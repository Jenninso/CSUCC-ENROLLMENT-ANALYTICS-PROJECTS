# Secure OTP Authentication

This app uses a hashed, single-use, time-limited email OTP for registration email proof, login 2FA, and password recovery.

## Install / configure

1. Pull the latest code and install dependencies if needed:
   ```bash
   cd laravel-app
   composer install
   ```
2. Run migrations (creates `otp_verifications`):
   ```bash
   php artisan migrate
   ```
3. Configure mail (Settings → Email / OTP sender, or `.env` SMTP).
4. Recommended `.env` values:
   ```env
   OTP_EXPIRES_MINUTES=5
   OTP_MAX_ATTEMPTS=5
   OTP_RESEND_COOLDOWN=60
   OTP_MAX_REQUESTS=5
   OTP_REQUEST_WINDOW_MINUTES=15
   OTP_MAX_REQUESTS_PER_IP=20
   OTP_LOGIN_ENABLED=true
   OTP_DEV_REVEAL=false

   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax
   # Set true when serving over HTTPS:
   SESSION_SECURE_COOKIE=true
   APP_URL=https://your-domain.example
   ```
5. Restart `php artisan serve` (or Apache) after changing `.env`.

### Development-only OTP reveal

- In `APP_ENV=testing`, OTP may be placed in the session as `otp_hint` so feature tests can verify flows.
- For local debugging when mail fails, you may set `OTP_DEV_REVEAL=true` **only** on non-production machines.
- Never enable `OTP_DEV_REVEAL` in production. Never use a fixed OTP such as `123456` in production.

## Flows

### Registration
Validate → session pending payload → OTP email → verify OTP → create `registration_requests` (`otp_verified_at`) → admin approval → user may log in.

### Login (2FA)
Email + password verified → **OTP only on first-time setup** (temporary password / incomplete credentials) → verify → regenerate session → `Auth::login` → password/credentials setup.

Returning users who already confirmed their password sign in with email + password only (no OTP).

### Forgot password
Employee ID → security questions → OTP → verify → set new password (hashed) → invalidate OTP.

## API surface (`App\Services\OtpService`)

| Method | Role |
|--------|------|
| `generateOtp()` | Cryptographically secure 6-digit code, store **hash**, invalidate prior active OTP |
| `sendOtp()` | Email the plaintext code once |
| `issue()` | generate + send (controller helper) |
| `verifyOtp()` | Checks existence, purpose, expiry, single-use, attempts, hash match |
| `resendOtp()` | Cooldown + regenerate |
| `invalidateOtp()` | Mark outstanding OTPs invalidated |

Purposes: `registration`, `login`, `password_reset`, `email_change`, `sensitive_action` (aliases `register`, `forgot_password` still accepted).

## Security checklist

- OTP stored only as `otp_hash` (bcrypt via Laravel `Hash`)
- 5-minute expiry, 5 attempts, 60s resend cooldown, per-email and per-IP request caps
- OTP value never written to audit logs or profile/password fields
- CSRF on OTP POST routes; session cookies HttpOnly + SameSite; enable Secure on HTTPS
- Prefer HTTPS for all auth pages in production

## SQL reference

See `database/sql/otp_verifications.sql` for a portable schema dump. Prefer `php artisan migrate` on this project.
