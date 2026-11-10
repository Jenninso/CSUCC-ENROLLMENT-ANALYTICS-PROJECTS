@extends('layouts.guest')
@section('title', $title ?? 'Verify OTP')

@section('content')
@php
    $expiresIn = (int) ($expiresIn ?? 0);
    $resendCooldown = (int) ($resendCooldown ?? 0);
@endphp
<div
    class="bg-white rounded-2xl shadow-xl p-8"
    x-data="{
        otp: '',
        expiresIn: {{ $expiresIn }},
        resendIn: {{ $resendCooldown }},
        init() {
            setInterval(() => {
                if (this.expiresIn > 0) this.expiresIn--;
                if (this.resendIn > 0) this.resendIn--;
            }, 1000);
            this.$nextTick(() => this.$refs.otpInput?.focus());
        },
        get expiryLabel() {
            const m = Math.floor(Math.max(0, this.expiresIn) / 60);
            const s = Math.max(0, this.expiresIn) % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },
        boxes() {
            const chars = (this.otp || '').padEnd(6, ' ').slice(0, 6).split('');
            return chars.map((c) => c === ' ' ? '' : c);
        },
        onOtpInput() {
            this.otp = (this.otp || '').replace(/\D/g, '').slice(0, 6);
        }
    }"
>
    <div class="text-center mb-6">
        <p class="text-[10px] uppercase tracking-[0.2em] text-amber-600 font-semibold">CSUCC</p>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">{{ $title ?? 'Verify Your Account' }}</h2>
        <p class="text-sm text-gray-500 mt-2">{{ $subtitle ?? 'Enter the 6-digit verification code sent to your registered email.' }}</p>
        @if(! empty($maskedEmail))
            <p class="text-xs text-emerald-800 mt-2 font-medium">{{ $maskedEmail }}</p>
        @endif
    </div>

    @if(session('error'))
        <p class="text-sm text-red-600 mb-3">{{ session('error') }}</p>
    @endif
    @if(session('success'))
        <p class="text-sm text-emerald-700 mb-3">{{ session('success') }}</p>
    @endif
    @if(session('otp_hint') && (app()->environment('testing') || config('otp.dev_reveal')))
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
            Development mode only — temporary PIN for this attempt: <strong>{{ session('otp_hint') }}</strong>
        </p>
    @endif
    @if($errors->any())
        <p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ $verifyRoute }}" class="space-y-5">
        @csrf
        <div class="relative">
            <div class="flex justify-center gap-2 pointer-events-none select-none" aria-hidden="true">
                <template x-for="(box, index) in boxes()" :key="index">
                    <div class="w-11 h-12 sm:w-12 sm:h-14 flex items-center justify-center text-xl font-semibold border rounded-lg bg-slate-50"
                         :class="box ? 'border-emerald-600 text-emerald-900' : 'border-gray-300 text-transparent'"
                         x-text="box || '•'"></div>
                </template>
            </div>
            <input
                x-ref="otpInput"
                type="text"
                name="otp"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                x-model="otp"
                @input="onOtpInput"
                class="absolute inset-0 w-full h-full opacity-0 caret-transparent"
                aria-label="6-digit verification code"
            >
        </div>

        <p class="text-center text-sm text-gray-600">
            OTP expires in:
            <span class="font-mono font-semibold text-emerald-800" x-text="expiryLabel">{{ sprintf('%02d:%02d', intdiv(max(0, $expiresIn), 60), max(0, $expiresIn) % 60) }}</span>
        </p>

        <button type="submit" class="w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold"
                :disabled="otp.length !== 6"
                :class="otp.length !== 6 ? 'opacity-60 cursor-not-allowed' : ''">
            {{ $submitLabel ?? 'Verify OTP' }}
        </button>
    </form>

    <div class="mt-5 text-center text-sm text-gray-500">
        <p>Didn't receive the code?</p>
        <form method="POST" action="{{ $resendRoute }}" class="mt-2">
            @csrf
            <button type="submit"
                    class="text-emerald-800 font-semibold disabled:text-gray-400 disabled:cursor-not-allowed"
                    :disabled="resendIn > 0"
                    @disabled($resendCooldown > 0)>
                <span x-show="resendIn <= 0">Resend OTP</span>
                <span x-cloak x-show="resendIn > 0">Resend OTP (<span x-text="resendIn"></span>s)</span>
            </button>
        </form>
    </div>
</div>
@endsection
