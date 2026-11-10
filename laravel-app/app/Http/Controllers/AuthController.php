<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginAttemptGuard;
use App\Services\LoginLogService;
use App\Services\OtpService;
use App\Support\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request, LoginAttemptGuard $guard)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return $this->loginView($request, $guard);
    }

    public function login(Request $request, AuditLogger $audit, LoginLogService $logs, LoginAttemptGuard $guard, OtpService $otp)
    {
        $request->merge([
            'username' => Username::normalize($request->input('username')),
        ]);

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $username = $credentials['username'];
        $ip = (string) $request->ip();
        $request->session()->put('login_attempt_username', $username);

        if ($guard->isLocked($ip, $username)) {
            $seconds = $guard->remaining($ip, $username);

            return back()->withErrors([
                'username' => $this->lockoutMessage($seconds),
            ])->onlyInput('username');
        }

        $user = User::query()->where('username', $username)->first();
        if ($user?->isBlocked()) {
            $guard->recordFailure($ip, $username);

            return back()->withErrors([
                'username' => 'This account is blocked. Contact a Super Administrator or Administrator.',
            ])->onlyInput('username');
        }

        $passwordOk = $user && Hash::check($credentials['password'], $user->password);
        $inactiveMessage = null;

        if ($passwordOk) {
            if ($user->isInactive()) {
                $passwordOk = false;
                $inactiveMessage = 'This account is Deactivated.';
            } elseif (! $user->isActive()) {
                $passwordOk = false;
                $inactiveMessage = 'This account is not active.';
            }
        }

        if (! $passwordOk) {
            $state = $guard->recordFailure($ip, $username);
            $message = $state['remaining'] > 0
                ? $this->lockoutMessage($state['remaining'])
                : ($inactiveMessage ?? 'The provided credentials do not match our records.');

            return back()->withErrors([
                'username' => $message,
            ])->onlyInput('username');
        }

        $guard->clear($ip, $username);
        $request->session()->forget('login_attempt_username');

        $needsLoginOtp = config('otp.login_enabled', true) && $user->requiresLoginOtp();

        if (! $needsLoginOtp) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $logs->timeIn($user, $request);
            $audit->log('login', 'authentication', 'Signed in.', $request, $user);

            return $this->postLoginRedirect($user);
        }

        $request->session()->put('login_otp', [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => $request->boolean('remember'),
        ]);

        $code = $otp->issue($user->email, 'login', $user, $request);
        if ($code === '') {
            $request->session()->forget('login_otp');

            return back()->withErrors([
                'username' => $otp->lastSendError ?: 'Please wait before requesting another code.',
            ])->onlyInput('username');
        }

        $redirect = redirect()->route('login.otp')->with('otp_hint', $otp->revealForLocal($code));
        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'Enter the verification code sent to your email to finish first-time sign-in.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
    }

    public function otpForm(Request $request, OtpService $otp)
    {
        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        $email = (string) ($pending['email'] ?? '');

        return view('auth.otp-verify', [
            'title' => 'Verify Your Account',
            'subtitle' => 'Enter the 6-digit verification code sent to your registered email.',
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'verifyRoute' => route('login.otp'),
            'resendRoute' => route('login.otp.resend'),
            'submitLabel' => 'Verify OTP',
            'expiresIn' => $otp->expiresInSeconds($email, 'login'),
            'resendCooldown' => $otp->resendCooldownSeconds($email, 'login'),
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otp, AuditLogger $audit, LoginLogService $logs)
    {
        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        $request->validate(['otp' => ['required', 'digits:6']]);
        $email = (string) ($pending['email'] ?? '');
        $user = User::query()->find($pending['user_id']);

        if (! $user || strcasecmp($user->email, $email) !== 0) {
            $request->session()->forget('login_otp');

            return redirect()->route('login')->withErrors(['username' => 'Your sign-in session expired. Please try again.']);
        }

        $result = $otp->verifyOtp($email, 'login', (string) $request->input('otp'), $request, $user);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['otp' => $result['message'] ?? 'Invalid verification code.']);
        }

        $remember = (bool) ($pending['remember'] ?? false);
        $request->session()->forget('login_otp');
        $request->session()->regenerate();
        Auth::login($user, $remember);
        $logs->timeIn($user, $request);
        $audit->log('login', 'authentication', 'Signed in after OTP verification.', $request, $user);

        return $this->postLoginRedirect($user);
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::query()->find($pending['user_id']);
        $email = (string) ($pending['email'] ?? $user?->email ?? '');
        if (! $user || $email === '') {
            return redirect()->route('login');
        }

        $result = $otp->resendOtp($email, 'login', $user, $request);
        $redirect = redirect()->route('login.otp')->with('otp_hint', $otp->revealForLocal($result['code'] ?? null));

        if (! ($result['ok'] ?? false)) {
            return $redirect->withErrors(['otp' => $result['message'] ?? 'Please wait before requesting another code.']);
        }

        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'A new verification code was sent.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
    }

    public function logout(Request $request, AuditLogger $audit, LoginLogService $logs)
    {
        $user = $request->user();
        $logs->timeOut($request);
        $audit->log('logout', 'authentication', 'Signed out.', $request, $user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function postLoginRedirect(User $user)
    {
        if ($user->mustChangePassword()) {
            return redirect()->route('setup.password');
        }
        if (! $user->hasCompletedCredentials()) {
            return redirect()->route('setup.credentials');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function loginView(Request $request, LoginAttemptGuard $guard)
    {
        $username = old('username') ?: $request->session()->get('login_attempt_username');
        $ip = (string) $request->ip();
        $hasUsername = is_string($username) && $username !== '';

        return view('auth.login', [
            'showForgotPassword' => $hasUsername && $guard->showForgotPassword($ip, $username),
            'lockoutSeconds' => $hasUsername ? $guard->remaining($ip, $username) : 0,
        ]);
    }

    private function lockoutMessage(int $seconds): string
    {
        return "Too many failed attempts. Try again in {$seconds} seconds.";
    }

    private function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $at = strpos($email, '@');
        if ($at === false) {
            return 'your email';
        }

        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $visible = max(1, min(2, strlen($local)));

        return substr($local, 0, $visible).str_repeat('*', max(1, strlen($local) - $visible)).'@'.$domain;
    }
}
