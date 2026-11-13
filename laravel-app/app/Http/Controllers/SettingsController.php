<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserAvatar;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function profile()
    {
        return view('settings.profile', [
            'user' => auth()->user(),
            'themeColors' => User::THEME_COLORS,
            'themeBackgrounds' => User::THEME_BACKGROUNDS,
        ]);
    }

    public function updateProfile(Request $request, AuditLogger $audit)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_avatar' => ['nullable', 'boolean'],
            'theme_mode' => ['required', Rule::in(['light', 'dark', 'system'])],
            'theme_color' => ['required', Rule::in(array_keys(User::THEME_COLORS))],
            'theme_background' => ['required', Rule::in(array_keys(User::THEME_BACKGROUNDS))],
        ]);

        $user->name = $validated['name'];
        $user->theme_mode = $validated['theme_mode'];
        $user->theme_color = $validated['theme_color'];
        $user->theme_background = $validated['theme_background'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if ($request->boolean('remove_avatar') && ($user->has_avatar || $user->avatar_path)) {
            $this->clearAvatar($user);
        }

        if ($request->hasFile('avatar')) {
            $this->clearAvatar($user);
            $file = $request->file('avatar');
            $mime = $file->getMimeType() ?: 'image/jpeg';
            UserAvatar::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'mime' => $mime,
                    'data' => base64_encode((string) file_get_contents($file->getRealPath())),
                ]
            );
            $user->has_avatar = true;
        }

        $user->save();

        $audit->log('update', 'profile', 'Updated profile appearance and account details.');

        return back()->with('success', 'Profile settings saved.');
    }

    public function avatar(User $user)
    {
        $stored = $user->avatar()->first();
        if ($stored && $stored->data) {
            $binary = base64_decode($stored->data, true);
            $mime = in_array($stored->mime, ['image/jpeg', 'image/png', 'image/webp'], true)
                ? $stored->mime
                : 'image/jpeg';

            if ($binary !== false) {
                return response($binary, 200, [
                    'Content-Type' => $mime,
                    'Cache-Control' => 'private, max-age=86400',
                ]);
            }
        }

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            return Storage::disk('public')->response($user->avatar_path);
        }

        abort(404);
    }

    private function clearAvatar(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar()->delete();
        $user->avatar_path = null;
        $user->has_avatar = false;
    }

    public function index()
    {
        $settings = [
            'institution_name' => SystemSetting::getValue('institution_name', 'Caraga State University Cabadbaran Campus'),
            'decline_threshold_pct' => SystemSetting::getValue('decline_threshold_pct', -10),
            'growth_threshold_pct' => SystemSetting::getValue('growth_threshold_pct', 15),
            'retention_target' => SystemSetting::getValue('retention_target', 85),
            'institution_capacity' => SystemSetting::getValue('institution_capacity', 5500),
            'mail_host' => SystemSetting::getValue('mail_host', env('MAIL_HOST', 'smtp.gmail.com')),
            'mail_port' => SystemSetting::getValue('mail_port', env('MAIL_PORT', 465)),
            'mail_username' => SystemSetting::getValue('mail_username', env('MAIL_USERNAME')),
            'mail_from_address' => SystemSetting::getValue('mail_from_address', env('MAIL_FROM_ADDRESS')),
            'mail_from_name' => SystemSetting::getValue('mail_from_name', env('MAIL_FROM_NAME', 'Enrollment Analytics')),
            'mail_configured' => app(\App\Services\MailSettings::class)->isConfigured() || app(\App\Services\BrevoMailer::class)->enabled(),
            'brevo_configured' => app(\App\Services\BrevoMailer::class)->enabled(),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'decline_threshold_pct' => ['required', 'numeric'],
            'growth_threshold_pct' => ['required', 'numeric'],
            'retention_target' => ['required', 'numeric', 'min:0', 'max:100'],
            'institution_capacity' => ['required', 'integer', 'min:1'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'email', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'brevo_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        foreach (['institution_name', 'decline_threshold_pct', 'growth_threshold_pct', 'retention_target', 'institution_capacity'] as $key) {
            SystemSetting::setValue($key, $validated[$key], 'alerts');
        }

        if ($request->user()?->isSuperAdmin()) {
            foreach (['mail_host', 'mail_port', 'mail_username', 'mail_from_address', 'mail_from_name'] as $key) {
                if (array_key_exists($key, $validated) && $validated[$key] !== null && $validated[$key] !== '') {
                    SystemSetting::setValue($key, $validated[$key], 'mail');
                }
            }
            if (! empty($validated['mail_password'])) {
                SystemSetting::setValue('mail_password', $validated['mail_password'], 'mail');
            }
            if (! empty($validated['brevo_api_key'])) {
                SystemSetting::setValue('brevo_api_key', $validated['brevo_api_key'], 'mail');
            }
        }

        $audit->log('update', 'settings', 'Updated system settings.');

        return back()->with('success', 'Settings saved.');
    }

    public function testMail(Request $request, \App\Services\OtpService $otp)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $email = $request->user()->email;
        $otp->issue($email, 'registration', null, $request);

        if ($otp->lastSendSucceeded) {
            return back()->with('success', 'A test OTP was sent to '.$email.'. Check that inbox (and spam).');
        }

        return back()->with('error', 'OTP email was not sent: '.($otp->lastSendError ?: 'SMTP is not configured.'));
    }

    public function auditLogs(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $logs = AuditLog::query()
            ->when($request->account_type, fn ($q, $v) => $q->where('role_name', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', 'like', '%'.strtoupper($v).'%'))
            ->when($request->date, fn ($q, $v) => $q->whereDate('created_at', $v))
            ->when($request->month, fn ($q, $v) => $q->whereMonth('created_at', $v)->whereYear('created_at', $request->year ?: now()->year))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $accountTypes = [
            'Super Administrator',
            'Administrator',
            'User',
        ];

        return view('settings.audit-logs', compact('logs', 'accountTypes'));
    }
}
