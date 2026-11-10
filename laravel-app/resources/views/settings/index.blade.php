@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="max-w-xl mb-4">
    <a href="{{ route('profile.edit') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-[var(--theme-primary)]"></span>
        Open profile picture, theme color, and dark mode
    </a>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="max-w-xl bg-white dark-panel rounded-lg shadow-sm p-6 space-y-4">
    @csrf
    <h3 class="font-semibold">Alert thresholds</h3>
    <label class="block text-sm">Institution name
        <input name="institution_name" value="{{ $settings['institution_name'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Decline threshold (%)
        <input type="number" step="0.1" name="decline_threshold_pct" value="{{ $settings['decline_threshold_pct'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Rapid growth threshold (%)
        <input type="number" step="0.1" name="growth_threshold_pct" value="{{ $settings['growth_threshold_pct'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Retention target (%)
        <input type="number" step="0.1" name="retention_target" value="{{ $settings['retention_target'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Planning capacity
        <input type="number" name="institution_capacity" value="{{ $settings['institution_capacity'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    @if(auth()->user()?->isSuperAdmin())
    <h3 class="font-semibold pt-2">Email / OTP sender</h3>
    <p class="text-xs text-gray-500">The free website cannot open Gmail’s mail ports. Create a free Brevo account, verify the Gmail sender, then paste the Brevo API key here. Account emails and OTP codes are sent over HTTPS.</p>
    <label class="block text-sm">Brevo API key
        <input type="password" name="brevo_api_key" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="{{ ($settings['brevo_configured'] ?? false) ? 'Leave blank to keep the saved key' : 'xkeysib-...' }}" autocomplete="off">
    </label>
    @if($settings['mail_configured'])
        <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">SMTP is configured. OTP codes will be emailed.</p>
    @else
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">SMTP is not configured yet. OTP cannot be delivered until you save the sender email and App Password below.</p>
    @endif
    <label class="block text-sm">SMTP host
        <input name="mail_host" value="{{ $settings['mail_host'] }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="smtp.gmail.com">
    </label>
    <label class="block text-sm">SMTP port
        <input type="number" name="mail_port" value="{{ $settings['mail_port'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    <label class="block text-sm">Gmail address (sender)
        <input type="email" name="mail_username" value="{{ $settings['mail_username'] }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="you@gmail.com">
    </label>
    <label class="block text-sm">Gmail App Password
        <input type="password" name="mail_password" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="{{ $settings['mail_configured'] ? 'Leave blank to keep the saved password' : 'xxxx xxxx xxxx xxxx' }}">
    </label>
    <label class="block text-sm">From address
        <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Same as Gmail address">
    </label>
    <label class="block text-sm">From name
        <input name="mail_from_name" value="{{ $settings['mail_from_name'] }}" class="mt-1 w-full border rounded-lg px-3 py-2">
    </label>
    @endif
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="px-4 py-2 bg-[var(--theme-primary)] rounded-lg text-sm border border-gray-300 shadow-sm" style="color: var(--theme-on-primary, #ffffff);">Save settings</button>
    </div>
</form>
@if(auth()->user()?->isSuperAdmin())
<form method="POST" action="{{ route('settings.test-mail') }}" class="max-w-xl mt-3">
    @csrf
    <button class="px-4 py-2 bg-slate-100 text-slate-800 rounded-lg text-sm">Send test OTP to my email</button>
</form>
@endif
@endsection
