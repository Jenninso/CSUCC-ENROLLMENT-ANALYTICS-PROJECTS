@extends('layouts.app')
@section('title', 'Confirm account deletion')

@section('content')
@php
    $me = auth()->user();
    $expected = strtoupper((string) $user->employee_id);
    $isSuperTarget = $user->isSuperAdmin();
@endphp
<div class="max-w-lg mx-auto" @if($isSuperTarget) x-data="{ confirmText: @js(old('confirm_employee_id', '')) }" @endif>
    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Confirm account deletion</h2>
        <p class="text-sm text-gray-600">
            This permanently deletes the {{ $user->roleName() ?: 'account' }}. This cannot be undone.
            Re-enter your email and password to proceed.
        </p>
        <div class="text-sm bg-red-50 border border-red-100 text-red-900 rounded-lg p-4 space-y-1">
            <p><span class="font-medium">Employee ID:</span> <span class="font-mono">{{ $user->employee_id }}</span></p>
            <p><span class="font-medium">Name:</span> {{ $user->fullName() }}</p>
            <p><span class="font-medium">Email:</span> {{ $user->email }}</p>
            <p><span class="font-medium">Role:</span> {{ $user->roleName() }}</p>
            <p><span class="font-medium">Status:</span> {{ $user->status }}</p>
        </div>
        <form method="POST" action="{{ route('users.destroy', $user) }}" class="space-y-4">
            @csrf @method('DELETE')

            @if($isSuperTarget)
            <div>
                <label for="confirm_employee_id" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="font-mono">{{ $user->employee_id }}</span> to confirm</label>
                <input id="confirm_employee_id" type="text" name="confirm_employee_id" x-model="confirmText" value="{{ old('confirm_employee_id') }}" autocomplete="off" required
                    class="w-full border rounded-lg px-3 py-2 text-sm font-mono @error('confirm_employee_id') border-red-500 @enderror"
                    placeholder="{{ $user->employee_id }}">
                @error('confirm_employee_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div>
                <label for="confirm_email" class="block text-sm font-medium text-gray-700 mb-1">Your email</label>
                <input id="confirm_email" type="email" name="confirm_email" value="{{ old('confirm_email') }}" required autocomplete="username"
                    class="w-full border rounded-lg px-3 py-2 text-sm @error('confirm_email') border-red-500 @enderror"
                    placeholder="{{ $me->email }}">
                @error('confirm_email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Your password</label>
                <input id="confirm_password" type="password" name="confirm_password" required autocomplete="current-password"
                    class="w-full border rounded-lg px-3 py-2 text-sm @error('confirm_password') border-red-500 @enderror"
                    placeholder="Current password">
                @error('confirm_password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Cancel</a>
                <button type="submit"
                    @if($isSuperTarget) :disabled="confirmText.trim().toUpperCase() !== @js($expected)" @endif
                    class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white disabled:bg-red-200 disabled:text-red-400 disabled:cursor-not-allowed">
                    Permanently delete
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
