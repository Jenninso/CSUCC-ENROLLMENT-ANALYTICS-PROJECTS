@extends('layouts.guest')
@section('title', 'Verify OTP')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">Email OTP</h2>
    <p class="text-sm text-gray-500 mb-4 text-center">A one-time PIN was sent to {{ $email }}.</p>
    @if(session('error'))
        <p class="text-sm text-red-600 mb-3">{{ session('error') }}</p>
    @endif
    @if(session('success'))
        <p class="text-sm text-emerald-700 mb-3">{{ session('success') }}</p>
    @endif
    @if(session('otp_hint'))
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">The email could not be delivered, so this temporary PIN is shown for this attempt only: <strong>{{ session('otp_hint') }}</strong></p>
    @endif
    @if($errors->any())
        <p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('register.otp') }}" class="space-y-4">
        @csrf
        <input name="otp" inputmode="numeric" maxlength="6" required class="w-full text-center tracking-[0.5em] text-xl px-4 py-3 border rounded-lg">
        <button class="w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold">Verify and submit for approval</button>
    </form>
</div>
@endsection
