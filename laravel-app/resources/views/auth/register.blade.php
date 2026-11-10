@extends('layouts.guest')
@section('title', 'Create Account')
@section('container-class', 'max-w-2xl')

@section('content')
<div class="text-center mb-4">
    <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC Logo" class="mx-auto w-12 h-12 object-contain mb-1.5 rounded-full bg-black/20 p-0.5">
    <p class="text-[10px] uppercase tracking-[0.2em] text-amber-300/90 font-semibold">CSUCC</p>
    <h1 class="text-xl font-bold text-white mt-0.5">Create Account</h1>
    <p class="text-emerald-100/90 text-xs mt-1">OTP will be sent to your email, then an admin must approve your account. By registering you agree to the <a href="{{ route('terms') }}" class="text-amber-200 underline underline-offset-2 hover:text-white">Terms and Conditions</a>.</p>
</div>

@php
    $hasErrors = $errors->any();
    $stepHint = 1;
    if ($hasErrors) {
        $identityErrors = collect($errors->keys())->contains(fn ($k) => in_array($k, ['employee_id', 'first_name', 'last_name', 'email', 'birthdate', 'sex', 'purok_street', 'barangay', 'province', 'city_municipality', 'zipcode', 'contact_number'], true));
        $qErrors = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'question_') || str_starts_with($k, 'answer_'));
        $pwErrors = collect($errors->keys())->contains(fn ($k) => str_contains($k, 'password'));
        if ($identityErrors) {
            $stepHint = 1;
        } elseif ($pwErrors) {
            $stepHint = 2;
        } elseif ($qErrors) {
            $stepHint = 3;
        }
    }
@endphp

<div
    x-data="{
        step: {{ $stepHint }},
        checkUrl: {{ json_encode(route('register.check')) }},
        employeeIdTaken: {{ $errors->has('employee_id') ? 'true' : 'false' }},
        employeeIdMessage: {{ json_encode($errors->first('employee_id')) }},
        employeeIdChecking: false,
        employeeIdAvailable: false,
        emailTaken: {{ $errors->has('email') ? 'true' : 'false' }},
        emailMessage: {{ json_encode($errors->first('email')) }},
        emailChecking: false,
        emailAvailable: false,
        emailCategoryLabel: '',
        employeeTimer: null,
        emailTimer: null,
        password: '',
        passwordConfirm: '',
        showPassword: false,
        showPasswordConfirm: false,
        firstNameError: {{ json_encode($errors->first('first_name') ?: '') }},
        middleNameError: {{ json_encode($errors->first('middle_name') ?: '') }},
        lastNameError: {{ json_encode($errors->first('last_name') ?: '') }},
        employeeId: {{ json_encode(old('employee_id', '')) }},
        firstName: {{ json_encode(old('first_name', '')) }},
        lastName: {{ json_encode(old('last_name', '')) }},
        email: {{ json_encode(old('email', '')) }},
        birthdate: {{ json_encode(old('birthdate', '')) }},
        sex: {{ json_encode(old('sex', '')) }},
        purokStreet: {{ json_encode(old('purok_street', '')) }},
        barangay: {{ json_encode(old('barangay', '')) }},
        cityMunicipality: {{ json_encode(old('city_municipality', '')) }},
        province: {{ json_encode(old('province', '')) }},
        zipcode: {{ json_encode(old('zipcode', '')) }},
        contactNumber: {{ json_encode(old('contact_number', '')) }},
        birthdateError: {{ json_encode($errors->first('birthdate') ?: '') }},
        sexError: {{ json_encode($errors->first('sex') ?: '') }},
        purokError: {{ json_encode($errors->first('purok_street') ?: '') }},
        barangayError: {{ json_encode($errors->first('barangay') ?: '') }},
        cityError: {{ json_encode($errors->first('city_municipality') ?: '') }},
        provinceError: {{ json_encode($errors->first('province') ?: '') }},
        zipError: {{ json_encode($errors->first('zipcode') ?: '') }},
        contactError: {{ json_encode($errors->first('contact_number') ?: '') }},
        employeeIdRequired: '',
        emailRequired: '',
        passwordRequired: '',
        passwordConfirmRequired: '',
        attempted: false,
        q1: {{ json_encode((string) old('question_1', '')) }},
        q2: {{ json_encode((string) old('question_2', '')) }},
        q3: {{ json_encode((string) old('question_3', '')) }},
        init() {
            if (this.$refs.employeeId?.value) this.checkField('employee_id');
            if (this.$refs.email?.value) this.checkField('email');
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
        nameError(value, required) {
            const v = value || '';
            if (!v.trim()) return required ? 'Enter a valid name.' : '';
            if (/\d/.test(v)) return 'Numbers are not allowed in the name.';
            if (/\s{2,}/.test(v)) return 'Double spacing is not allowed in the name.';
            if (v !== v.trim()) return 'Leading or trailing spaces are not allowed in the name.';
            if (/[^A-Za-zÑñ '\\-]/.test(v)) return 'Use letters only. Hyphens and apostrophes are allowed.';
            if (/([A-Za-zÑñ])\1\1/i.test(v.toLowerCase())) return 'Three consecutive letters of the same kind are not allowed.';
            const words = v.split(/\s+/);
            for (const word of words) {
                if (!word) continue;
                const parts = word.split(/['\\-]/);
                for (const part of parts) {
                    if (!part) return 'Use a letter after a hyphen or apostrophe.';
                    if (part[0] !== part[0].toUpperCase() || part[0] === part[0].toLowerCase()) {
                        return 'The first letter of the name must be a capital letter.';
                    }
                    const rest = part.slice(1);
                    if (rest && rest !== rest.toLowerCase()) {
                        return 'After the first capital letter, use small letters only. Names like JoHn are not allowed.';
                    }
                }
            }
            return '';
        },
        ageFromBirthdate() {
            if (!this.birthdate) return '';
            const born = new Date(this.birthdate + 'T00:00:00');
            if (Number.isNaN(born.getTime())) return '';
            const today = new Date();
            let age = today.getFullYear() - born.getFullYear();
            const month = today.getMonth() - born.getMonth();
            if (month < 0 || (month === 0 && today.getDate() < born.getDate())) age--;
            return age >= 0 ? String(age) : '';
        },
        digitsOnly(value) {
            return (value || '').replace(/\D/g, '');
        },
        step1Ready() {
            return this.employeeId.trim() !== ''
                && this.firstName.trim() !== ''
                && this.lastName.trim() !== ''
                && this.email.trim() !== ''
                && this.birthdate
                && this.sex
                && this.purokStreet.trim() !== ''
                && this.barangay.trim() !== ''
                && this.cityMunicipality.trim() !== ''
                && this.province.trim() !== ''
                && this.zipcode.trim() !== ''
                && this.contactNumber.trim() !== ''
                && !this.firstNameError
                && !this.lastNameError
                && !this.middleNameError
                && !this.birthdateError
                && !this.contactError
                && !this.employeeIdTaken
                && !this.emailTaken
                && !this.employeeIdChecking
                && !this.emailChecking
                && (!this.$refs.email || this.$refs.email.checkValidity());
        },
        markEmptyRequired() {
            this.attempted = true;
            this.employeeIdRequired = this.employeeId.trim() ? '' : 'Employee ID is required.';
            this.emailRequired = this.email.trim() ? '' : 'Email is required.';
            if (!this.firstName.trim()) this.firstNameError = 'First name is required.';
            if (!this.lastName.trim()) this.lastNameError = 'Last name is required.';
            this.birthdateError = this.birthdate ? this.birthdateError : 'Birthdate is required.';
            this.sexError = this.sex ? '' : 'Sex is required.';
            this.purokError = this.purokStreet.trim() ? '' : 'Purok/Street is required.';
            this.barangayError = this.barangay.trim() ? '' : 'Barangay is required.';
            this.cityError = this.cityMunicipality.trim() ? '' : 'City/Municipality is required.';
            this.provinceError = this.province.trim() ? '' : 'Province is required.';
            this.zipError = this.zipcode.trim() ? '' : 'Zip code is required.';
            this.contactError = this.contactNumber.trim() ? this.contactError : 'Contact number is required.';
            if (this.step === 2) {
                this.passwordRequired = this.password ? '' : 'Password is required.';
                this.passwordConfirmRequired = this.passwordConfirm ? '' : 'Confirm password is required.';
            }
        },
        syncNameValidity(el, required, errorKey) {
            const value = el.value || '';
            const liveError = value.trim() ? this.nameError(value, false) : '';
            this[errorKey] = liveError;
            el.setCustomValidity(this.nameError(value, required));
        },
        questionTaken(id, current) {
            const value = String(id);
            return [this.q1, this.q2, this.q3].filter((selected) => selected && selected !== current).includes(value);
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
        async checkField(kind) {
            const input = kind === 'employee_id' ? this.$refs.employeeId : this.$refs.email;
            const value = (input?.value || '').trim();
            const takenKey = kind === 'employee_id' ? 'employeeIdTaken' : 'emailTaken';
            const messageKey = kind === 'employee_id' ? 'employeeIdMessage' : 'emailMessage';
            const checkingKey = kind === 'employee_id' ? 'employeeIdChecking' : 'emailChecking';
            const availableKey = kind === 'employee_id' ? 'employeeIdAvailable' : 'emailAvailable';

            if (!value || (kind === 'email' && !input.checkValidity())) {
                this[takenKey] = false;
                this[messageKey] = null;
                this[availableKey] = false;
                this[checkingKey] = false;
                if (kind === 'email') this.emailCategoryLabel = '';
                return;
            }

            this[checkingKey] = true;
            try {
                const query = kind === 'employee_id'
                    ? '?employee_id=' + encodeURIComponent(value)
                    : '?email=' + encodeURIComponent(value);
                const res = await fetch(this.checkUrl + query, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const status = data[kind];
                this[takenKey] = Boolean(status && !status.available);
                this[messageKey] = status?.message || null;
                this[availableKey] = Boolean(status && status.available);
                if (kind === 'email') {
                    this.emailCategoryLabel = status?.account_category_label || '';
                }
                input.setCustomValidity(this[takenKey] ? (this[messageKey] || 'This value is already in use.') : '');
            } catch (e) {
                this[takenKey] = false;
                this[availableKey] = false;
                if (kind === 'email') this.emailCategoryLabel = '';
            } finally {
                this[checkingKey] = false;
            }
        },
        go(next) {
            this.markEmptyRequired();
            const section = this.$refs['step' + this.step];
            if (!section) return;
            if (this.step === 1 && !this.step1Ready()) {
                if (!this.employeeId.trim()) this.$refs.employeeId?.focus();
                else if (!this.firstName.trim() || this.firstNameError) this.$refs.firstName?.focus();
                else if (!this.lastName.trim() || this.lastNameError) this.$refs.lastName?.focus();
                else this.$refs.email?.focus();
                return;
            }
            const fields = section.querySelectorAll('input, select, textarea');
            for (const field of fields) {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    return;
                }
            }
            if (this.step === 1 && (this.employeeIdTaken || this.emailTaken)) {
                if (this.employeeIdTaken) this.$refs.employeeId?.focus();
                else this.$refs.email?.focus();
                return;
            }
            if (this.step === 2) {
                this.syncPasswordValidity();
                if (!this.password || !this.passwordConfirm || !this.passwordAcceptable() || !this.passwordsMatch()) {
                    (this.passwordAcceptable() ? this.$refs.passwordConfirm : this.$refs.password)?.focus();
                    return;
                }
            }
            this.step = next;
            this.attempted = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }"
    class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 mb-6 overflow-hidden"
>
    <div class="px-4 pt-4 sm:px-6 sm:pt-5">
        <div class="flex items-center gap-2 mb-4">
            <template x-for="n in 3" :key="n">
                <div class="flex-1 flex items-center gap-2">
                    <div class="h-1.5 flex-1 rounded-full" :class="step >= n ? 'bg-[#0b5d3b]' : 'bg-gray-200'"></div>
                </div>
            </template>
        </div>
        <p class="text-xs text-gray-500 mb-3">
            Step <span x-text="step"></span> of 3 —
            <span x-show="step === 1">Personal information</span>
            <span x-show="step === 2" x-cloak>Password</span>
            <span x-show="step === 3" x-cloak>Secret questions</span>
        </p>
    </div>

    <div class="px-4 pb-5 sm:px-6 sm:pb-6 max-h-[min(75vh,720px)] overflow-y-auto overscroll-contain">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg mb-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-3" id="register-form">
            @csrf

            <section x-show="step === 1" x-ref="step1" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID <span class="text-red-600">*</span></label>
                    <input name="employee_id" x-model="employeeId" required autocomplete="username"
                           x-ref="employeeId"
                           :class="(employeeIdRequired || employeeIdTaken) ? 'border-red-400 focus:ring-red-500' : (employeeIdAvailable ? 'border-emerald-400 focus:ring-emerald-600' : 'border-gray-300 focus:ring-emerald-600')"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none uppercase"
                               @input="
                               $el.value = $el.value.toUpperCase();
                               employeeId = $el.value;
                               employeeIdTaken = false;
                               employeeIdAvailable = false;
                               employeeIdMessage = null;
                               employeeIdRequired = '';
                               $el.setCustomValidity('');
                               clearTimeout(employeeTimer);
                               employeeTimer = setTimeout(() => checkField('employee_id'), 400);
                           "
                           @blur="
                               if (!employeeId.trim()) employeeIdRequired = 'Employee ID is required.';
                               else { employeeIdRequired = ''; checkField('employee_id'); }
                           ">
                    <p x-show="employeeIdChecking" x-cloak class="text-xs text-gray-500 mt-1">Checking Employee ID…</p>
                    <p x-show="employeeIdRequired" x-cloak class="text-xs text-red-600 mt-1" x-text="employeeIdRequired"></p>
                    <p x-show="!employeeIdRequired && employeeIdTaken && employeeIdMessage" x-cloak class="text-xs text-red-600 mt-1" x-text="employeeIdMessage"></p>
                    <p x-show="employeeIdAvailable && !employeeIdTaken && !employeeIdRequired" x-cloak class="text-xs text-emerald-700 mt-1">This Employee ID is available.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-600">*</span></label>
                        <input name="first_name" x-model="firstName" required x-ref="firstName"
                               @input="syncNameValidity($el, true, 'firstNameError')"
                               @blur="if (!firstName.trim()) firstNameError = 'First name is required.'; else syncNameValidity($el, true, 'firstNameError')"
                               :class="firstNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="firstNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="firstNameError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input name="middle_name" value="{{ old('middle_name') }}" placeholder="Optional"
                               @input="syncNameValidity($el, false, 'middleNameError')" @blur="syncNameValidity($el, false, 'middleNameError')"
                               :class="middleNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="middleNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="middleNameError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-600">*</span></label>
                        <input name="last_name" x-model="lastName" required x-ref="lastName"
                               @input="syncNameValidity($el, true, 'lastNameError')"
                               @blur="if (!lastName.trim()) lastNameError = 'Last name is required.'; else syncNameValidity($el, true, 'lastNameError')"
                               :class="lastNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="lastNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="lastNameError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Extension Name</label>
                        <select name="extension_name" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">None</option>
                            @foreach($extensions as $extension)
                                <option value="{{ $extension }}" @selected(old('extension_name') === $extension)>{{ $extension }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate <span class="text-red-600">*</span></label>
                        <input type="date" name="birthdate" x-model="birthdate" required
                               :max="new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().slice(0,10)"
                               @input="birthdateError = ''"
                               @blur="if (!birthdate) birthdateError = 'Birthdate is required.'; else if (Number(ageFromBirthdate()) < 18) birthdateError = 'You must be at least 18 years old.'; else birthdateError = '';"
                               :class="birthdateError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="birthdateError" x-cloak class="text-xs text-red-600 mt-1" x-text="birthdateError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="text" :value="ageFromBirthdate()" readonly tabindex="-1"
                               placeholder="Auto"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-slate-50 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sex <span class="text-red-600">*</span></label>
                        <select name="sex" x-model="sex" required
                                @change="sexError = sex ? '' : 'Sex is required.'"
                                :class="sexError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm bg-white focus:ring-2 outline-none">
                            <option value="">Select…</option>
                            @foreach($sexes as $sexOption)
                                <option value="{{ $sexOption }}">{{ $sexOption }}</option>
                            @endforeach
                        </select>
                        <p x-show="sexError" x-cloak class="text-xs text-red-600 mt-1" x-text="sexError"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number <span class="text-red-600">*</span></label>
                    <input name="contact_number" x-model="contactNumber" required inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX"
                           @input="
                               if (/[A-Za-z]/.test($el.value)) contactError = 'Letters are not allowed in the contact number.';
                               contactNumber = digitsOnly($el.value).slice(0, 11);
                               $el.value = contactNumber;
                               if (contactNumber.length === 11 && /^09\d{9}$/.test(contactNumber)) contactError = '';
                           "
                           @blur="if (!contactNumber) contactError = 'Contact number is required.'; else if (!/^09\d{9}$/.test(contactNumber)) contactError = 'Enter an 11-digit mobile number starting with 09.';"
                           :class="contactError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                    <p x-show="contactError" x-cloak class="text-xs text-red-600 mt-1" x-text="contactError"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purok/Street <span class="text-red-600">*</span></label>
                        <input name="purok_street" x-model="purokStreet" required
                               @blur="purokError = purokStreet.trim() ? '' : 'Purok/Street is required.'"
                               :class="purokError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="purokError" x-cloak class="text-xs text-red-600 mt-1" x-text="purokError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barangay <span class="text-red-600">*</span></label>
                        <input name="barangay" x-model="barangay" required
                               @blur="barangayError = barangay.trim() ? '' : 'Barangay is required.'"
                               :class="barangayError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="barangayError" x-cloak class="text-xs text-red-600 mt-1" x-text="barangayError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City/Municipality <span class="text-red-600">*</span></label>
                        <input name="city_municipality" x-model="cityMunicipality" required
                               @blur="cityError = cityMunicipality.trim() ? '' : 'City/Municipality is required.'"
                               :class="cityError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="cityError" x-cloak class="text-xs text-red-600 mt-1" x-text="cityError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province <span class="text-red-600">*</span></label>
                        <input name="province" x-model="province" required
                               @blur="provinceError = province.trim() ? '' : 'Province is required.'"
                               :class="provinceError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="provinceError" x-cloak class="text-xs text-red-600 mt-1" x-text="provinceError"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code <span class="text-red-600">*</span></label>
                        <input name="zipcode" x-model="zipcode" required inputmode="numeric" maxlength="4"
                               @input="zipcode = digitsOnly($el.value).slice(0, 4); $el.value = zipcode; zipError = '';"
                               @blur="zipError = /^\d{4}$/.test(zipcode) ? '' : 'Enter a 4-digit zip code.';"
                               :class="zipError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none">
                        <p x-show="zipError" x-cloak class="text-xs text-red-600 mt-1" x-text="zipError"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-600">*</span></label>
                    <input type="email" name="email" x-model="email" required autocomplete="email"
                           x-ref="email"
                           :class="(emailRequired || emailTaken) ? 'border-red-400 focus:ring-red-500' : (emailAvailable ? 'border-emerald-400 focus:ring-emerald-600' : 'border-gray-300 focus:ring-emerald-600')"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 outline-none"
                           @input="
                               emailTaken = false;
                               emailAvailable = false;
                               emailMessage = null;
                               emailCategoryLabel = '';
                               emailRequired = '';
                               $el.setCustomValidity('');
                               clearTimeout(emailTimer);
                               emailTimer = setTimeout(() => checkField('email'), 400);
                           "
                           @blur="
                               if (!email.trim()) emailRequired = 'Email is required.';
                               else { emailRequired = ''; checkField('email'); }
                           ">
                    <p x-show="emailChecking" x-cloak class="text-xs text-gray-500 mt-1">Checking email…</p>
                    <p x-show="emailRequired" x-cloak class="text-xs text-red-600 mt-1" x-text="emailRequired"></p>
                    <p x-show="!emailRequired && emailTaken && emailMessage" x-cloak class="text-xs text-red-600 mt-1" x-text="emailMessage"></p>
                    <p x-show="emailAvailable && !emailTaken && !emailRequired" x-cloak class="text-xs text-emerald-700 mt-1">This email is available.</p>
                    <p x-show="emailCategoryLabel && !emailRequired" x-cloak class="text-xs text-slate-600 mt-1">
                        Recognized as <span class="font-semibold" x-text="emailCategoryLabel"></span>
                        <span x-show="emailCategoryLabel === 'Institutional Account'"> (campus email domain).</span>
                        <span x-show="emailCategoryLabel === 'Personal Account'"> (non-campus email).</span>
                    </p>
                </div>
            </section>

            <section x-show="step === 2" x-cloak x-ref="step2" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password"
                               x-ref="password" x-model="password" minlength="8"
                               @input="passwordRequired = ''; syncPasswordValidity()"
                               @blur="if (!password) passwordRequired = 'Password is required.';"
                               class="w-full px-3 py-2.5 pr-20 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-600 outline-none">
                        <button type="button" class="absolute inset-y-0 right-2 text-xs font-medium text-emerald-800 hover:underline"
                                @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                    </div>
                    <div class="mt-2 space-y-2" x-show="password.length > 0 && !passwordAcceptable()" x-cloak aria-live="polite">
                        <div class="flex gap-1" role="meter" aria-label="Password strength"
                             :aria-valuetext="passwordLevel()">
                            <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'weak' ? 'bg-red-500' : (passwordLevel() === 'medium' ? 'bg-amber-500' : 'bg-emerald-600')"></div>
                            <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'medium' ? 'bg-amber-500' : (passwordLevel() === 'strong' ? 'bg-emerald-600' : 'bg-gray-200')"></div>
                            <div class="h-1.5 flex-1 rounded-full" :class="passwordLevel() === 'strong' ? 'bg-emerald-600' : 'bg-gray-200'"></div>
                        </div>
                        <p class="text-xs font-medium text-red-600"
                           x-text="'Strength: ' + passwordLevel().charAt(0).toUpperCase() + passwordLevel().slice(1)"></p>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1 text-xs text-red-600">
                            <li x-show="!passwordChecks().length">○ 8+ characters</li>
                            <li x-show="!passwordChecks().uppercase">○ Uppercase letter</li>
                            <li x-show="!passwordChecks().lowercase">○ Lowercase letter</li>
                            <li x-show="!passwordChecks().number">○ Number</li>
                        </ul>
                    </div>
                    <p x-show="passwordRequired" x-cloak class="text-xs text-red-600 mt-1" x-text="passwordRequired"></p>
                    <p x-show="password.length > 0 && passwordAcceptable()" x-cloak class="text-xs font-medium mt-2"
                       :class="passwordLevel() === 'strong' ? 'text-emerald-700' : 'text-amber-700'"
                       x-text="'Strength: ' + passwordLevel().charAt(0).toUpperCase() + passwordLevel().slice(1)"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <input :type="showPasswordConfirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                               x-ref="passwordConfirm" x-model="passwordConfirm"
                               @input="passwordConfirmRequired = ''; syncPasswordValidity()"
                               @blur="if (!passwordConfirm) passwordConfirmRequired = 'Confirm password is required.';"
                               class="w-full px-3 py-2.5 pr-20 border rounded-lg text-sm focus:ring-2 outline-none"
                               :class="passwordConfirmRequired || (passwordConfirm && !passwordsMatch()) ? 'border-red-400 focus:ring-red-500' : (passwordsMatch() ? 'border-emerald-400 focus:ring-emerald-600' : 'border-gray-300 focus:ring-emerald-600')">
                        <button type="button" class="absolute inset-y-0 right-2 text-xs font-medium text-emerald-800 hover:underline"
                                @click="showPasswordConfirm = !showPasswordConfirm" x-text="showPasswordConfirm ? 'Hide' : 'Show'"></button>
                    </div>
                    <p x-show="passwordConfirmRequired" x-cloak class="text-xs text-red-600 mt-1" x-text="passwordConfirmRequired"></p>
                    <p x-show="!passwordConfirmRequired && passwordConfirm && !passwordsMatch()" x-cloak class="text-xs text-red-600 mt-1">Passwords do not match.</p>
                    <p x-show="passwordsMatch()" x-cloak class="text-xs text-emerald-700 mt-1">Passwords match.</p>
                </div>
            </section>

            <section x-show="step === 3" x-cloak x-ref="step3" class="space-y-3">
                <p class="text-xs text-gray-500">There are 9 recovery questions. Choose 3 different ones and answer them. Forgot password will later show these 3 in a random order.</p>
                @for($i = 1; $i <= 3; $i++)
                <div class="rounded-lg bg-emerald-50/80 border border-emerald-100 p-3 space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Question {{ $i }} of 3 <span class="text-red-600">*</span></label>
                    <select name="question_{{ $i }}" required x-model="q{{ $i }}"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Select a question…</option>
                        @foreach($questions as $q)
                            <option value="{{ $q->id }}" :disabled="questionTaken({{ $q->id }}, q{{ $i }})" @selected(old('question_'.$i) == $q->id)>{{ $q->question }}</option>
                        @endforeach
                    </select>
                    <input type="password" name="answer_{{ $i }}" required placeholder="Your answer *" autocomplete="off"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>
                @endfor
            </section>
        </form>
    </div>

    <div class="border-t border-gray-100 bg-white/90 px-4 py-3 sm:px-6 flex flex-wrap items-center gap-2 justify-between">
        <button type="button" x-show="step > 1" x-cloak @click="step--; window.scrollTo({top:0,behavior:'smooth'})"
                class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">
            Back
        </button>
        <span x-show="step === 1" class="text-sm text-gray-500">
            Already registered?
            <a href="{{ route('login') }}" class="text-emerald-700 font-medium hover:underline">Sign in</a>
        </span>
        <div class="ml-auto flex gap-2">
            <button type="button" x-show="step < 3"
                    @click="go(step + 1)"
                    :disabled="(step === 1 && (employeeIdTaken || emailTaken || employeeIdChecking || emailChecking)) || (step === 2 && password && passwordConfirm && (!passwordAcceptable() || !passwordsMatch()))"
                    :class="((step === 1 && (employeeIdTaken || emailTaken || employeeIdChecking || emailChecking)) || (step === 2 && password && passwordConfirm && (!passwordAcceptable() || !passwordsMatch()))) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#094d31]'"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#0b5d3b]">
                Continue
            </button>
            <button type="submit" form="register-form" x-show="step === 3" x-cloak
                    @click="
                        const form = document.getElementById('register-form');
                        if (form && !form.checkValidity()) { $event.preventDefault(); form.reportValidity(); }
                    "
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#0b5d3b] hover:bg-[#094d31]">
                Send OTP to Email
            </button>
        </div>
    </div>
</div>
@endsection
