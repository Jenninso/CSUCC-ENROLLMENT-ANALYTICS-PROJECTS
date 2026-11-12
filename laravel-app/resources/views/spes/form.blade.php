@extends('layouts.app')
@section('title', isset($application) ? 'Edit SPES Application' : 'New SPES Application')

@php
    $app = $application ?? null;
    $prefill = $prefill ?? [];
    $studentMode = ! empty($studentMode);
    $formAction = isset($application)
        ? ($studentMode ? route('student.spes.update', $application) : route('spes.update', $application))
        : ($studentMode ? route('student.spes.store') : route('spes.store'));
    $cancelUrl = isset($application)
        ? ($studentMode ? route('student.spes.show', $application) : route('spes.show', $application))
        : ($studentMode ? route('student.spes.index') : route('spes.index'));
    $value = function (string $field, mixed $default = '') use ($app, $prefill) {
        $current = $app?->{$field} ?? $prefill[$field] ?? $default;
        if ($current instanceof \Illuminate\Support\Carbon) {
            $current = $current->toDateString();
        }
        return old($field, $current);
    };
    $checked = function (string $field) use ($app) {
        $current = old($field, $app?->{$field} ? '1' : '0');
        return (string) $current === '1';
    };
@endphp

@section('content')
<div class="max-w-5xl">
    <a href="{{ $cancelUrl }}" class="ui-link text-sm">← Back</a>
    <h3 class="ui-page-title mt-2">{{ isset($application) ? 'Edit SPES application' : 'SPES application form' }}</h3>
    <p class="ui-page-sub mb-5">For students and out-of-school youth ages 15 to 30. Work runs 20 to 78 days. The employer pays 40% of the wage and DOLE pays 60%. PESO checks family income against the regional poverty threshold.</p>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="space-y-5" @if($studentMode) onsubmit="return confirm('Double check your Form before saving. After the office approves it, you cannot edit it unless the handler allows another edit.')" @endif>
        @csrf
        @if(isset($application)) @method('PUT') @endif
        <input type="hidden" name="student_record_id" value="{{ $value('student_record_id') }}">

        <section class="ui-panel p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">1. Personal information</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last name</label>
                    <input type="text" name="last_name" value="{{ $value('last_name') }}" required maxlength="80" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First name</label>
                    <input type="text" name="first_name" value="{{ $value('first_name') }}" required maxlength="80" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle name</label>
                    <input type="text" name="middle_name" value="{{ $value('middle_name') }}" maxlength="80" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extension</label>
                    <input type="text" name="name_extension" value="{{ $value('name_extension') }}" maxlength="20" placeholder="Jr., III" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                    <select name="sex" required class="ui-input">
                        <option value="">Select</option>
                        @foreach(\App\Models\SpesApplication::SEXES as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('sex') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Civil status</label>
                    <select name="civil_status" required class="ui-input">
                        <option value="">Select</option>
                        @foreach(\App\Models\SpesApplication::CIVIL_STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('civil_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Birth date</label>
                    <input type="date" name="birth_date" value="{{ $value('birth_date') }}" required class="ui-input">
                    <p class="text-xs text-gray-400 mt-1">Age is computed. It must be 15 to 30.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Place of birth</label>
                    <input type="text" name="place_of_birth" value="{{ $value('place_of_birth') }}" required maxlength="150" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                    @if($studentMode)
                    <input type="hidden" name="student_id" value="{{ $value('student_id') }}">
                    <input type="text" value="{{ $value('student_id') }}" class="ui-input bg-gray-50" readonly>
                    @else
                    <input type="text" name="student_id" value="{{ $value('student_id') }}" maxlength="30" placeholder="Optional" class="ui-input">
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">House no. / street</label>
                    <input type="text" name="address" value="{{ $value('address') }}" required maxlength="255" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                    <input type="text" name="barangay" value="{{ $value('barangay') }}" required maxlength="100" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City / municipality</label>
                    <input type="text" name="city_municipality" value="{{ $value('city_municipality') }}" required maxlength="100" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                    <input type="text" name="province" value="{{ $value('province') }}" required maxlength="100" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ZIP code</label>
                    <input type="text" name="zip_code" value="{{ $value('zip_code') }}" maxlength="10" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact number</label>
                    <input type="text" name="contact_number" value="{{ $value('contact_number') }}" required maxlength="30" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ $value('email') }}" maxlength="150" class="ui-input">
                </div>
            </div>
        </section>

        <section class="ui-panel p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">2. Family and income</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Father’s name</label>
                    <input type="text" name="father_name" value="{{ $value('father_name') }}" maxlength="150" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Father’s occupation</label>
                    <input type="text" name="father_occupation" value="{{ $value('father_occupation') }}" maxlength="100" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother’s name</label>
                    <input type="text" name="mother_name" value="{{ $value('mother_name') }}" maxlength="150" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother’s occupation</label>
                    <input type="text" name="mother_occupation" value="{{ $value('mother_occupation') }}" maxlength="100" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parents’ combined annual income (PHP)</label>
                    <input type="number" name="parents_annual_income" value="{{ $value('parents_annual_income') }}" required min="0" step="0.01" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Number of siblings</label>
                    <input type="number" name="number_of_siblings" value="{{ $value('number_of_siblings', 0) }}" required min="0" max="30" class="ui-input">
                </div>
            </div>
        </section>

        <section class="ui-panel p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">3. Education</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <select name="education_level" required class="ui-input">
                        @foreach(\App\Models\SpesApplication::EDUCATION_LEVELS as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('education_level', 'tertiary') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
                    <select name="enrollment_status" required class="ui-input">
                        @foreach(\App\Models\SpesApplication::ENROLLMENT_STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('enrollment_status', 'enrolled') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year level</label>
                    <select name="year_level" required class="ui-input">
                        <option value="">Select</option>
                        @foreach(\App\Models\SpesApplication::YEAR_LEVELS as $label)
                            <option value="{{ $label }}" {{ (string) $value('year_level') === $label ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">School</label>
                    <input type="text" name="school_name" value="{{ $value('school_name', 'Caraga State University — Cabadbaran Campus') }}" required maxlength="180" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">General average</label>
                    <input type="number" name="general_average" value="{{ $value('general_average') }}" min="0" max="100" step="0.01" class="ui-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">School address</label>
                    <input type="text" name="school_address" value="{{ $value('school_address') }}" maxlength="255" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Course / strand</label>
                    <input type="text" name="course" value="{{ $value('course') }}" maxlength="150" class="ui-input">
                </div>
            </div>
        </section>

        <section class="ui-panel p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">4. SPES engagement</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application year</label>
                    <input type="number" name="application_year" value="{{ $value('application_year', now()->year) }}" required min="1992" max="{{ now()->year + 1 }}" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work period</label>
                    <select name="work_period" required class="ui-input">
                        @foreach(\App\Models\SpesApplication::WORK_PERIODS as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('work_period', 'summer') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Working days</label>
                    <input type="number" name="preferred_days" value="{{ $value('preferred_days', 20) }}" required min="20" max="78" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Beneficiary type</label>
                    <select name="beneficiary_type" required class="ui-input">
                        @foreach(\App\Models\SpesApplication::BENEFICIARY_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('beneficiary_type', 'student') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Previous SPES beneficiary</label>
                    <select name="previous_spes" required class="ui-input">
                        <option value="0" {{ (string) $value('previous_spes', '0') === '0' || $value('previous_spes') === false ? 'selected' : '' }}>No</option>
                        <option value="1" {{ (string) $value('previous_spes') === '1' || $value('previous_spes') === true ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Previous year</label>
                    <input type="number" name="previous_year" value="{{ $value('previous_year') }}" min="1992" max="{{ now()->year }}" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred assignment</label>
                    <input type="text" name="preferred_assignment" value="{{ $value('preferred_assignment') }}" maxlength="150" class="ui-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                    <input type="text" name="skills" value="{{ $value('skills') }}" maxlength="255" class="ui-input">
                </div>
            </div>
        </section>

        <section class="ui-panel p-6 space-y-3">
            <h4 class="text-sm font-semibold text-gray-900">5. Documents on file</h4>
            <p class="text-xs text-gray-500">Check each document the office has already received. Saving does not upload the file.</p>
            @foreach(\App\Models\SpesApplication::DOCUMENTS as $field => $label)
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="hidden" name="{{ $field }}" value="0">
                <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 rounded border-gray-300" {{ $checked($field) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </section>

        <section class="ui-panel p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">6. Certification and office status</h4>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="certified" value="1" class="mt-0.5 rounded border-gray-300" {{ old('certified', isset($application) ? '1' : '') ? 'checked' : '' }} required>
                <span>I certify that the information in this application is true, that I am a poor but deserving student or out-of-school youth, and that I will finish the SPES work period and continue my studies.</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Applicant’s printed name</label>
                    <input type="text" name="applicant_signature" value="{{ $value('applicant_signature') }}" required maxlength="150" class="ui-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application date</label>
                    <input type="date" name="application_date" value="{{ $value('application_date', now()->toDateString()) }}" required class="ui-input">
                </div>
                @unless($studentMode)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Office status</label>
                    <select name="status" required class="ui-input">
                        @foreach(\App\Models\SpesApplication::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ (string) $value('status', 'submitted') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="sm:col-span-2 lg:col-span-3 flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="allow_student_edit" value="1" class="mt-0.5 rounded border-gray-300" {{ old('allow_student_edit', $app->student_can_edit ?? false) ? 'checked' : '' }}>
                    <span>Allow the student to edit after approval. Leave this off to keep an approved application locked for the student.</span>
                </label>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="3" maxlength="2000" class="ui-input">{{ $value('remarks') }}</textarea>
                </div>
                @else
                <input type="hidden" name="status" value="{{ $value('status', 'submitted') }}">
                @endunless
            </div>
        </section>

        <div class="flex items-center gap-3">
            @if($studentMode)
            <div class="w-full rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <p class="font-semibold">Double check your Form.</p>
                <p class="mt-1">This will lock after approval. You can still change a submitted application. Once the office approves it, you cannot edit it anymore. It becomes editable again only when the handler of the record allows another edit, and it locks again after you save.</p>
            </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="ui-btn-primary">Save application</button>
            <a href="{{ $cancelUrl }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>
@endsection
