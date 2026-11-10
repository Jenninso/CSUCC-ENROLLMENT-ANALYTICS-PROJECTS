@extends('layouts.guest')
@section('title', 'New Password')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8"
     x-data="{
        password: '',
        passwordConfirm: '',
        showPassword: false,
        showPasswordConfirm: false,
        passwordChecks() {
            const p = this.password || '';
            return {
                length: p.length >= 8,
                uppercase: /[A-Z]/.test(p),
                lowercase: /[a-z]/.test(p),
                number: /\d/.test(p),
                symbol: /[^A-Za-z0-9]/.test(p),
                long: p.length >= 12,
            };
        },
        passwordScore() {
            return Object.values(this.passwordChecks()).filter(Boolean).length;
        },
        passwordLevel() {
            if (!this.password) return 'empty';
            const score = this.passwordScore();
            if (score <= 2) return 'weak';
            if (score <= 4) return 'medium';
            return 'strong';
        },
        passwordAcceptable() {
            const checks = this.passwordChecks();
            return checks.length && checks.uppercase && checks.lowercase && checks.number && this.passwordLevel() !== 'weak';
        },
        passwordsMatch() {
            return this.password !== '' && this.password === this.passwordConfirm;
        },
        strengthLabel() {
            const level = this.passwordLevel();
            return level === 'empty' ? '' : 'Strength: ' + level.charAt(0).toUpperCase() + level.slice(1);
        },
        syncPasswordValidity() {
            const passwordField = this.$refs.password;
            const confirmField = this.$refs.passwordConfirm;
            if (passwordField) {
                passwordField.setCustomValidity(this.password && !this.passwordAcceptable()
                    ? 'Use at least 8 characters with uppercase, lowercase, and a number. Weak passwords are not allowed.'
                    : '');
            }
            if (confirmField) {
                confirmField.setCustomValidity(this.passwordConfirm && this.passwordConfirm !== this.password
                    ? 'Passwords do not match.'
                    : '');
            }
        },
     }">
    <p class="text-xs text-emerald-700 font-medium mb-1">Step 3 of 3</p>
    <h2 class="text-xl font-semibold mb-2 text-center">Change password</h2>
    @if(isset($user))
        <p class="text-sm text-gray-500 mb-4 text-center">{{ $user->fullName() }} ({{ $user->employee_id }})</p>
    @endif
    @if($errors->any())<p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>@endif
    <form method="POST" action="{{ route('password.reset') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password"
                       x-ref="password" x-model="password" minlength="8"
                       @input="syncPasswordValidity()"
                       class="w-full px-4 py-2.5 pr-16 border rounded-lg">
                <button type="button" class="absolute inset-y-0 right-3 text-xs font-medium text-emerald-800 hover:underline"
                        @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
            </div>
            <div class="mt-2 space-y-2" x-show="password.length > 0" x-cloak aria-live="polite">
                <div class="flex gap-1" role="meter" aria-label="Password strength" :aria-valuetext="passwordLevel()">
                    <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'weak' ? 'bg-red-500' : (passwordLevel() === 'medium' ? 'bg-amber-500' : 'bg-emerald-600')"></div>
                    <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'medium' ? 'bg-amber-500' : (passwordLevel() === 'strong' ? 'bg-emerald-600' : 'bg-gray-200')"></div>
                    <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'strong' ? 'bg-emerald-600' : 'bg-gray-200'"></div>
                </div>
                <p class="text-xs font-medium"
                   :class="passwordLevel() === 'weak' ? 'text-red-600' : (passwordLevel() === 'medium' ? 'text-amber-700' : 'text-emerald-700')"
                   x-text="strengthLabel()"></p>
                <ul x-show="!passwordAcceptable()" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1 text-xs text-red-600">
                    <li x-show="!passwordChecks().length">○ 8+ characters</li>
                    <li x-show="!passwordChecks().uppercase">○ Uppercase letter</li>
                    <li x-show="!passwordChecks().lowercase">○ Lowercase letter</li>
                    <li x-show="!passwordChecks().number">○ Number</li>
                </ul>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
            <div class="relative">
                <input :type="showPasswordConfirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                       x-ref="passwordConfirm" x-model="passwordConfirm"
                       @input="syncPasswordValidity()"
                       class="w-full px-4 py-2.5 pr-16 border rounded-lg">
                <button type="button" class="absolute inset-y-0 right-3 text-xs font-medium text-emerald-800 hover:underline"
                        @click="showPasswordConfirm = !showPasswordConfirm" x-text="showPasswordConfirm ? 'Hide' : 'Show'"></button>
            </div>
            <p x-show="passwordConfirm && !passwordsMatch()" x-cloak class="text-xs text-red-600 mt-1">Passwords do not match.</p>
            <p x-show="passwordsMatch()" x-cloak class="text-xs text-emerald-700 mt-1">Passwords match.</p>
        </div>
        <button class="w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold disabled:opacity-50"
                :disabled="!passwordAcceptable() || !passwordsMatch()">Update password</button>
    </form>
</div>
@endsection
