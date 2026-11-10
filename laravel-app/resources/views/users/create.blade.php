@extends('layouts.app')
@section('title', 'Create Super Admin / Administrator / User')

@section('content')
<div class="max-w-xl">
    <p class="text-sm text-gray-500 mb-4"></p>
    <div class="bg-white rounded-lg shadow-sm p-6"
         x-data="{
            username: @js(old('username', '')),
            email: @js(old('email', '')),
            usernameTouched: {{ old('username') ? 'true' : 'false' }},
            password: '',
            passwordConfirm: '',
            showPassword: false,
            showPasswordConfirm: false,
            emailLabel() {
                const v = (this.email || '').toLowerCase();
                const domains = @js(config('accounts.institutional_email_domains'));
                if (!v.includes('@')) return '';
                const domain = v.split('@').pop();
                const hit = domains.some(d => domain === d || domain.endsWith('.' + d));
                return hit ? 'Institutional Account' : 'Personal Account';
            },
            suggestUsernameFromEmail() {
                if (this.usernameTouched) return;
                const local = (this.email || '').split('@')[0] || '';
                this.username = local.toLowerCase().replace(/[^a-z0-9._-]/g, '').slice(0, 50);
            },
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
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID / ID Number</label>
                <input value="{{ $nextEmployeeId }}" readonly class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 text-gray-600">
                <p class="text-xs text-gray-400 mt-1">Auto-incremented. This ID is assigned when you save.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username (used to sign in)</label>
                <input type="text" name="username" x-model="username" required autocomplete="username"
                       @input="usernameTouched = true"
                       pattern="[a-z0-9](?:[a-z0-9._-]{1,48}[a-z0-9])?"
                       class="w-full px-3 py-2 border rounded-lg text-sm"
                       placeholder="*****.*****">
                @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1">Lowercase letters, numbers, dots, underscores, or hyphens. This is what the user types on the login screen.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email (OTP &amp; notifications)</label>
                <input type="email" name="email" x-model="email" required
                       @input="suggestUsernameFromEmail()"
                       class="w-full px-3 py-2 border rounded-lg text-sm"
                       placeholder="*****.*******@csucc.edu.ph">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500 mt-1" x-show="email.includes('@')" x-cloak>
                    Recognized as <span class="font-medium" x-text="emailLabel()"></span> from the email domain.
                </p>
                <p class="text-xs text-gray-400 mt-1">Not used for sign-in. Campus emails (e.g. @csucc.edu.ph) are Institutional; other providers are Personal. OTP codes and credentials are sent here.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Temporary password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password"
                           x-ref="password" x-model="password" minlength="8"
                           @input="syncPasswordValidity()"
                           class="w-full px-3 py-2 pr-14 border rounded-lg text-sm">
                    <button type="button" class="absolute inset-y-0 right-3 text-xs font-medium text-indigo-700 hover:underline"
                            @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                </div>
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm temporary password</label>
                <div class="relative">
                    <input :type="showPasswordConfirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                           x-ref="passwordConfirm" x-model="passwordConfirm"
                           @input="syncPasswordValidity()"
                           class="w-full px-3 py-2 pr-14 border rounded-lg text-sm">
                    <button type="button" class="absolute inset-y-0 right-3 text-xs font-medium text-indigo-700 hover:underline"
                            @click="showPasswordConfirm = !showPasswordConfirm" x-text="showPasswordConfirm ? 'Hide' : 'Show'"></button>
                </div>
                <p x-show="passwordConfirm && !passwordsMatch()" x-cloak class="text-xs text-red-600 mt-1">Passwords do not match.</p>
                <p x-show="passwordsMatch()" x-cloak class="text-xs text-emerald-700 mt-1">Passwords match.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->role_name }}</option>
                    @endforeach
                </select>
                @error('role_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1">Select Role to assign to the new account.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50"
                        :disabled="!passwordAcceptable() || !passwordsMatch()">Create account</button>
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
