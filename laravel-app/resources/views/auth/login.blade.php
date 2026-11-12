@extends('layouts.guest')
@section('title', 'Sign In')
@section('container-class', 'max-w-3xl')

@section('content')
<div class="mx-auto w-full overflow-hidden rounded-2xl border border-white/15 shadow-[0_18px_50px_rgba(0,0,0,0.32)] lg:grid lg:grid-cols-2">
<div class="relative border-b border-white/10 px-7 py-8 text-center sm:px-8 lg:flex lg:flex-col lg:justify-center lg:border-b-0 lg:border-r lg:px-10 lg:py-11 lg:text-left">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[#04281c]/60 backdrop-blur-xl"></div>
    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-0.5 bg-[#d4a017]" aria-hidden="true"></div>
    <div class="relative z-10 flex items-center justify-center gap-3.5 lg:justify-start">
        <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC Logo"
             class="h-14 w-14 shrink-0 rounded-full bg-black/25 object-contain p-1 ring-1 ring-white/30 lg:h-16 lg:w-16">
        <div class="text-left">
            <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-amber-200/90">CSUCC</p>
            <p class="mt-1 text-sm font-medium text-white/95">Enrollment Analytics</p>
        </div>
    </div>
    <h1 class="font-brand relative z-10 mt-8 text-[1.65rem] font-semibold leading-[1.2] text-white">
        Caraga State University<br>Cabadbaran Campus
    </h1>
    <div class="relative z-10 mx-auto mt-4 h-px w-10 bg-[#d4a017] lg:mx-0"></div>
    <p class="relative z-10 mx-auto mt-4 max-w-xs text-[13px] leading-relaxed text-white/75 lg:mx-0">Enrollment Analytics &amp; Decision Support for CEIT.</p>
</div>

@php
    $lockoutLabel = $lockoutSeconds >= 60
        ? intdiv($lockoutSeconds, 60).':'.str_pad((string) ($lockoutSeconds % 60), 2, '0', STR_PAD_LEFT)
        : $lockoutSeconds.'s';
@endphp
<div
    class="bg-white px-7 py-8 sm:px-8 lg:px-10 lg:py-11"
    x-data="{
        remaining: {{ (int) $lockoutSeconds }},
        timer: null,
        init() {
            if (this.remaining > 0) {
                this.timer = setInterval(() => {
                    if (this.remaining > 0) this.remaining--;
                    if (this.remaining <= 0 && this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                }, 1000);
            }
        },
        clock() {
            const minutes = Math.floor(this.remaining / 60);
            const seconds = this.remaining % 60;
            if (minutes > 0) {
                return minutes + ':' + String(seconds).padStart(2, '0');
            }
            return this.remaining + 's';
        }
    }"
>
    <div class="mb-6 border-b border-gray-100 pb-5">
        <h2 class="bg-gradient-to-r from-[#064e32] via-[#0b5d3b] to-[#1f9d5a] bg-clip-text text-3xl font-bold tracking-tight text-transparent">Sign in</h2>
        <p class="mt-1 text-sm text-gray-500">Use your campus account credentials</p>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($lockoutSeconds > 0)
    <div x-show="remaining > 0" x-cloak class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-xl mb-4 text-sm" aria-live="polite">
        <p class="font-medium">Sign in is paused after too many failed attempts.</p>
        <p class="mt-1">Try again in <span class="font-semibold tabular-nums" x-text="clock()">{{ $lockoutLabel }}</span>.</p>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="username" class="mb-1.5 block text-[13px] font-semibold text-gray-700">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                   autocomplete="username"
                   :disabled="remaining > 0"
                   placeholder=""
                   class="ui-input border-gray-200 bg-[#f7f8f7] disabled:bg-gray-100 disabled:text-gray-500">
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-[13px] font-semibold text-gray-700">Password</label>
            <input type="password" name="password" id="password" required
                   :disabled="remaining > 0"
                   class="ui-input border-gray-200 bg-[#f7f8f7] disabled:bg-gray-100 disabled:text-gray-500">
        </div>
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 text-[13px] text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#0b5d3b] focus:ring-[#0b5d3b]" :disabled="remaining > 0">
                Remember me
            </label>
            @if($showForgotPassword)
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-800 hover:underline">Forgot password</a>
            @endif
        </div>
        <button type="submit"
                :disabled="remaining > 0"
                :class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''"
                class="ui-btn-primary mt-1 w-full py-3 tracking-wide">
            <span x-show="remaining <= 0">Sign in</span>
            <span x-show="remaining > 0" x-cloak>Wait <span x-text="clock()">{{ $lockoutLabel }}</span></span>
        </button>
        <p class="pt-3 text-center text-sm text-gray-600">
            Student? <a href="{{ route('student.register') }}" class="font-medium text-emerald-800 hover:underline">Create a student account</a>
        </p>
    </form>
</div>
</div>
@endsection
