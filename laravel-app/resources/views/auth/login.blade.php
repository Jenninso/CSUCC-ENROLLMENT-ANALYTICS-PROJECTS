@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
<div class="text-center mb-8">
    <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC Logo"
         class="mx-auto w-24 h-24 object-contain drop-shadow-lg mb-5 rounded-full bg-black/25 p-1.5 ring-1 ring-white/20">
    <p class="text-[11px] uppercase tracking-[0.22em] text-amber-300/95 font-semibold">CSUCC</p>
    <h1 class="font-brand text-2xl sm:text-3xl font-semibold text-white mt-2 leading-snug">
        Caraga State University<br class="sm:hidden"> Cabadbaran Campus
    </h1>
    <p class="text-emerald-50/90 text-sm mt-3 max-w-sm mx-auto">Enrollment Analytics &amp; Decision Support for CEIT and BSIT.</p>
</div>

@php
    $lockoutLabel = $lockoutSeconds >= 60
        ? intdiv($lockoutSeconds, 60).':'.str_pad((string) ($lockoutSeconds % 60), 2, '0', STR_PAD_LEFT)
        : $lockoutSeconds.'s';
@endphp
<div
    class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-white/25"
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
    <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Sign in</h2>
        <p class="text-sm text-gray-500 mt-1">Use your campus account credentials</p>
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

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                   autocomplete="username"
                   :disabled="remaining > 0"
                   placeholder=""
                   class="ui-input disabled:bg-gray-100 disabled:text-gray-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <input type="password" name="password" id="password" required
                   :disabled="remaining > 0"
                   class="ui-input disabled:bg-gray-100 disabled:text-gray-500">
        </div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" :disabled="remaining > 0">
                Remember me
            </label>
            @if($showForgotPassword)
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-800 hover:underline">Forgot password</a>
            @endif
        </div>
        <button type="submit"
                :disabled="remaining > 0"
                :class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''"
                class="ui-btn-primary w-full py-3">
            <span x-show="remaining <= 0">Sign In</span>
            <span x-show="remaining > 0" x-cloak>Wait <span x-text="clock()">{{ $lockoutLabel }}</span></span>
        </button>
    </form>
</div>
@endsection
