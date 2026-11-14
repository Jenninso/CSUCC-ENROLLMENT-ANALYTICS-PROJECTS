@extends('layouts.guest')
@section('title', 'Sign In')
@section('container-class', 'max-w-4xl')

@section('content')
@php
    $lockoutLabel = $lockoutSeconds >= 60
        ? intdiv($lockoutSeconds, 60).':'.str_pad((string) ($lockoutSeconds % 60), 2, '0', STR_PAD_LEFT)
        : $lockoutSeconds.'s';
@endphp
<div class="mx-auto flex w-full flex-col items-center gap-8 lg:flex-row lg:justify-center lg:gap-12">
    <section class="max-w-md text-center text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.45)]">
        <span class="login-logo">
            <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC Logo"
                 class="h-32 w-32 object-contain sm:h-36 sm:w-36">
        </span>
        <p class="mt-3 text-sm font-semibold uppercase tracking-[0.32em] text-amber-200">CSUCC</p>
        <p class="mt-1 text-lg font-medium text-white">Enrollment Analytics</p>
        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.24em] text-amber-200">Cabadbaran Campus</p>
        <h1 class="font-brand mt-2 text-3xl font-semibold leading-tight text-white sm:text-4xl">
            Caraga State University Cabadbaran Campus
        </h1>
    </section>

    <div class="login-glow w-full max-w-sm">
    <div
        class="login-card bg-white px-6 py-6 sm:px-7"
        x-data="{
            remaining: {{ (int) $lockoutSeconds }},
            showPassword: false,
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
        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#0b5d3b]">Campus account</p>
        <h2 class="mt-1 bg-gradient-to-r from-[#064e32] via-[#0b5d3b] to-[#1f9d5a] bg-clip-text text-2xl font-bold tracking-tight text-transparent">Sign in</h2>
        <p class="mt-1 text-sm text-gray-500">Use your campus account credentials</p>

        @if(session('success'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($lockoutSeconds > 0)
        <div x-show="remaining > 0" x-cloak class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" aria-live="polite">
            <p class="font-medium">Sign in is paused after too many failed attempts.</p>
            <p class="mt-1">Try again in <span class="font-semibold tabular-nums" x-text="clock()">{{ $lockoutLabel }}</span>.</p>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-3">
            @csrf
            <div>
                <label for="username" class="mb-1.5 block text-[13px] font-semibold text-gray-700">Username</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-gray-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="8" r="3.2"/></svg>
                    </span>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                           autocomplete="username"
                           :disabled="remaining > 0"
                           class="ui-input border-gray-200 bg-gray-50 pl-10 disabled:bg-gray-100 disabled:text-gray-500">
                </div>
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-[13px] font-semibold text-gray-700">Password</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-gray-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="9" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                           :disabled="remaining > 0"
                           class="ui-input border-gray-200 bg-gray-50 pl-10 pr-16 disabled:bg-gray-100 disabled:text-gray-500">
                    <button type="button" class="absolute inset-y-0 right-3 text-xs font-semibold text-[#0b5d3b]" @click="showPassword = !showPassword" :disabled="remaining > 0">
                        <span x-text="showPassword ? 'Hide' : 'Show'">Show</span>
                    </button>
                </div>
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
                    class="ui-btn-primary mt-1 w-full rounded-xl py-2.5 tracking-wide">
                <span x-show="remaining <= 0">Sign in</span>
                <span x-show="remaining > 0" x-cloak>Wait <span x-text="clock()">{{ $lockoutLabel }}</span></span>
            </button>
        </form>
    </div>
    </div>
</div>
@endsection
