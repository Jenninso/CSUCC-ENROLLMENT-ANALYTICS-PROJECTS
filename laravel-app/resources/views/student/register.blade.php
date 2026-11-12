@extends('layouts.guest')
@section('title', 'Student Account')
@section('container-class', 'max-w-6xl')

@section('content')
<div class="rounded-2xl bg-white px-5 py-4 sm:px-6 text-gray-800">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-800">CSUCC · CEIT</p>
            <h1 class="mt-0.5 text-xl font-bold text-gray-900">Create a student account</h1>
        </div>
        <p class="text-sm text-gray-600 sm:text-right sm:max-w-md">Separate from staff. Sign in later with your Student ID. <a href="{{ route('login') }}" class="font-medium text-emerald-800 hover:underline">Sign in</a></p>
    </div>

    @if($errors->any())
    <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('student.register') }}" class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 lg:grid-cols-2">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 content-start">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold">Student ID</label>
                <input type="text" name="student_id" value="{{ old('student_id') }}" required maxlength="30" placeholder="2025-01001" class="ui-input !py-2 !text-sm">
                <p class="mt-1 text-[11px] text-gray-500">Must already be on an enrollment record. This is your username.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="ui-input !py-2 !text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="ui-input !py-2 !text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Middle name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="ui-input !py-2 !text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="ui-input !py-2 !text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Password</label>
                <input type="password" name="password" required class="ui-input !py-2 !text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Confirm password</label>
                <input type="password" name="password_confirmation" required class="ui-input !py-2 !text-sm">
                <p class="mt-1 text-[11px] text-gray-500">8+ characters, upper case, lower case, and a number.</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 lg:border-l lg:border-gray-100 lg:pl-6">
            <div>
                <h2 class="text-sm font-semibold">Security questions</h2>
                <p class="text-[11px] text-gray-500">Used if you forget your password. Each answer is stored as a hash, never as plain text.</p>
            </div>
            @foreach([1, 2, 3] as $i)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <select name="question_{{ $i }}" required class="ui-input !py-2 !text-sm">
                    <option value="">Question {{ $i }}</option>
                    @foreach($questions as $question)
                        <option value="{{ $question->id }}" {{ (string) old('question_'.$i) === (string) $question->id ? 'selected' : '' }}>{{ $question->question }}</option>
                    @endforeach
                </select>
                <input type="password" name="answer_{{ $i }}" required placeholder="Answer {{ $i }}" autocomplete="off" class="ui-input !py-2 !text-sm">
            </div>
            @endforeach
            <button type="submit" class="ui-btn-primary mt-auto w-full py-2.5">Send verification code</button>
        </div>
    </form>
</div>
@endsection
