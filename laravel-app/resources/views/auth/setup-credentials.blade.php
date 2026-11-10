@extends('layouts.guest')
@section('title', 'Complete credentials')
@section('container-class', 'max-w-xl')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8"
     x-data="{
        firstName: {{ json_encode(old('first_name', $user->first_name)) }},
        middleName: {{ json_encode(old('middle_name', $user->middle_name)) }},
        lastName: {{ json_encode(old('last_name', $user->last_name)) }},
        address: {{ json_encode(old('address', $user->address)) }},
        q1: {{ json_encode((string) old('question_1', '')) }},
        q2: {{ json_encode((string) old('question_2', '')) }},
        q3: {{ json_encode((string) old('question_3', '')) }},
        answer1: {{ json_encode(old('answer_1', '')) }},
        answer2: {{ json_encode(old('answer_2', '')) }},
        answer3: {{ json_encode(old('answer_3', '')) }},
        firstNameError: {{ json_encode($errors->first('first_name') ?: '') }},
        middleNameError: {{ json_encode($errors->first('middle_name') ?: '') }},
        lastNameError: {{ json_encode($errors->first('last_name') ?: '') }},
        question1Error: {{ json_encode($errors->first('question_1') ?: '') }},
        question2Error: {{ json_encode($errors->first('question_2') ?: '') }},
        question3Error: {{ json_encode($errors->first('question_3') ?: '') }},
        answer1Error: {{ json_encode($errors->first('answer_1') ?: '') }},
        answer2Error: {{ json_encode($errors->first('answer_2') ?: '') }},
        answer3Error: {{ json_encode($errors->first('answer_3') ?: '') }},
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
        hasDuplicateQuestion(current) {
            const value = String(current || '');
            if (!value) return false;
            return [this.q1, this.q2, this.q3].filter((selected) => String(selected) === value).length > 1;
        },
        syncQuestionValidity(el, current, errorKey, requiredLabel) {
            if (!current) {
                this[errorKey] = requiredLabel;
                el.setCustomValidity(requiredLabel);
                return;
            }
            if (this.hasDuplicateQuestion(current)) {
                this[errorKey] = 'Choose a different security question.';
                el.setCustomValidity('Choose a different security question.');
                return;
            }
            this[errorKey] = '';
            el.setCustomValidity('');
        },
        syncAnswerValidity(el, value, errorKey, label) {
            const trimmed = (value || '').trim();
            this[errorKey] = trimmed ? '' : label;
            el.setCustomValidity(trimmed ? '' : label);
        },
        submitForm(event) {
            const form = event.target;
            this.syncNameValidity(this.$refs.firstName, true, 'firstNameError');
            this.syncNameValidity(this.$refs.middleName, false, 'middleNameError');
            this.syncNameValidity(this.$refs.lastName, true, 'lastNameError');
            this.syncQuestionValidity(this.$refs.question1, this.q1, 'question1Error', 'Question 1 is required.');
            this.syncQuestionValidity(this.$refs.question2, this.q2, 'question2Error', 'Question 2 is required.');
            this.syncQuestionValidity(this.$refs.question3, this.q3, 'question3Error', 'Question 3 is required.');
            this.syncAnswerValidity(this.$refs.answer1, this.answer1, 'answer1Error', 'Answer 1 is required.');
            this.syncAnswerValidity(this.$refs.answer2, this.answer2, 'answer2Error', 'Answer 2 is required.');
            this.syncAnswerValidity(this.$refs.answer3, this.answer3, 'answer3Error', 'Answer 3 is required.');
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        }
     }">
    <h2 class="text-xl font-semibold mb-2">Complete your credentials</h2>
    <p class="text-sm text-gray-500 mb-4">Fill in your information and three security questions. You can enter the dashboard after this step.</p>
    @if(session('success'))<p class="text-sm text-emerald-700 mb-3">{{ session('success') }}</p>@endif
    @if($errors->any())
        <ul class="text-sm text-red-600 mb-3 list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form method="POST" action="{{ route('setup.credentials') }}" class="space-y-4" @submit="submitForm($event)">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">First name</label>
                <input name="first_name" x-model="firstName" x-ref="firstName" required
                       @input="syncNameValidity($el, true, 'firstNameError')"
                       @blur="if (!firstName.trim()) firstNameError = 'First name is required.'; else syncNameValidity($el, true, 'firstNameError')"
                       :class="firstNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                       class="w-full px-3 py-2.5 border rounded-lg outline-none focus:ring-2">
                <p x-show="firstNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="firstNameError"></p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Middle name</label>
                <input name="middle_name" x-model="middleName" x-ref="middleName"
                       @input="syncNameValidity($el, false, 'middleNameError')"
                       @blur="syncNameValidity($el, false, 'middleNameError')"
                       :class="middleNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                       class="w-full px-3 py-2.5 border rounded-lg outline-none focus:ring-2">
                <p x-show="middleNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="middleNameError"></p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Last name</label>
                <input name="last_name" x-model="lastName" x-ref="lastName" required
                       @input="syncNameValidity($el, true, 'lastNameError')"
                       @blur="if (!lastName.trim()) lastNameError = 'Last name is required.'; else syncNameValidity($el, true, 'lastNameError')"
                       :class="lastNameError ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                       class="w-full px-3 py-2.5 border rounded-lg outline-none focus:ring-2">
                <p x-show="lastNameError" x-cloak class="text-xs text-red-600 mt-1" x-text="lastNameError"></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Address <span class="text-gray-400">(optional)</span></label>
            <input name="address" x-model="address" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-emerald-600">
        </div>
        <p class="text-sm font-medium pt-2">Security questions</p>
        <p class="text-xs text-gray-500">Choose 3 different questions from the list of 9. Forgot password will show these in a random order.</p>
        <div class="space-y-3">
        @for($i = 1; $i <= 3; $i++)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
            <select name="question_{{ $i }}" required x-model="q{{ $i }}" x-ref="question{{ $i }}"
                    @change="syncQuestionValidity($el, q{{ $i }}, 'question{{ $i }}Error', 'Question {{ $i }} is required.')"
                    @blur="syncQuestionValidity($el, q{{ $i }}, 'question{{ $i }}Error', 'Question {{ $i }} is required.')"
                    :class="question{{ $i }}Error ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                    class="w-full px-3 py-2.5 border rounded-lg text-sm outline-none focus:ring-2 bg-white">
                <option value="">Question {{ $i }} of 3</option>
                @foreach($questions as $q)
                    <option value="{{ $q->id }}" :disabled="questionTaken({{ $q->id }}, q{{ $i }})" @selected(old('question_'.$i) == $q->id)>{{ $q->question }}</option>
                @endforeach
            </select>
            <p x-show="question{{ $i }}Error" x-cloak class="text-xs text-red-600 mt-1" x-text="question{{ $i }}Error"></p>
            </div>
            <div>
            <input type="password" name="answer_{{ $i }}" required placeholder="Answer {{ $i }}" autocomplete="off"
                   x-model="answer{{ $i }}" x-ref="answer{{ $i }}"
                   @input="syncAnswerValidity($el, answer{{ $i }}, 'answer{{ $i }}Error', 'Answer {{ $i }} is required.')"
                   @blur="syncAnswerValidity($el, answer{{ $i }}, 'answer{{ $i }}Error', 'Answer {{ $i }} is required.')"
                   :class="answer{{ $i }}Error ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-emerald-600'"
                   class="w-full px-3 py-2.5 border rounded-lg outline-none focus:ring-2">
            <p x-show="answer{{ $i }}Error" x-cloak class="text-xs text-red-600 mt-1" x-text="answer{{ $i }}Error"></p>
            </div>
        </div>
        @endfor
        </div>
        <button class="w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold">Save and enter dashboard</button>
    </form>
</div>
@endsection
