@extends('layouts.guest')
@section('title', 'Verify account')
@section('container-class', 'max-w-6xl')

@section('content')
<div class="bg-white rounded-2xl shadow-xl px-5 py-4 sm:px-6 sm:py-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between lg:gap-6 mb-4">
        <div class="min-w-0 lg:max-w-2xl">
            <p class="text-xs text-emerald-700 font-medium">Step 2 of 3</p>
            <h2 class="text-xl font-semibold mt-0.5">Security questions and email</h2>
            <p class="text-sm text-gray-500 mt-1">Choose the 3 questions you saved at registration from the 9 options, then enter those answers and the email registered to this Employee ID.</p>
        </div>
        @if(isset($user))
            <p class="text-sm text-gray-700 bg-slate-50 border rounded-lg px-4 py-2.5 shrink-0 lg:w-64">
                <span class="text-xs text-gray-500 block">Employee ID</span>
                <span class="font-semibold">{{ $user->employee_id }}</span>
                <span class="block text-gray-600">{{ $user->fullName() }}</span>
            </p>
        @endif
    </div>

    @if(session('success'))<p class="text-sm text-emerald-700 mb-3">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>@endif

    @if(! $otpSent)
    <form method="POST" action="{{ route('password.questions') }}"
          x-data="{
              q1: {{ json_encode((string) old('question_1', '')) }},
              q2: {{ json_encode((string) old('question_2', '')) }},
              q3: {{ json_encode((string) old('question_3', '')) }},
              taken(id, current) {
                  const value = String(id);
                  return [this.q1, this.q2, this.q3].filter((selected) => selected && selected !== current).includes(value);
              }
          }">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @for($i = 1; $i <= 3; $i++)
            <div>
                <label class="block text-sm font-medium mb-1">Question {{ $i }} of 3</label>
                <select name="question_{{ $i }}" required x-model="q{{ $i }}"
                        class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Select a question…</option>
                    @foreach($questions as $q)
                        <option value="{{ $q->id }}" :disabled="taken({{ $q->id }}, q{{ $i }})" @selected(old('question_'.$i) == $q->id)>{{ $q->question }}</option>
                    @endforeach
                </select>
                <input type="password" name="answer_{{ $i }}" value="{{ old('answer_'.$i) }}" required autocomplete="off"
                       placeholder="Your answer" class="w-full px-3 py-2 border rounded-lg text-sm mt-2">
            </div>
            @endfor
        </div>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_11rem] gap-3 sm:items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Email (OTP will be sent here)</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Registered email">
            </div>
            <button class="w-full bg-[#0b5d3b] text-white py-2 rounded-lg font-semibold">Send OTP</button>
        </div>
    </form>
    @else
    @if(session('error'))<p class="text-sm text-red-600 mb-3">{{ session('error') }}</p>@endif
    @if(session('otp_hint'))
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">The email could not be delivered, so this temporary PIN is shown for this attempt only: <strong>{{ session('otp_hint') }}</strong></p>
    @endif
    <form method="POST" action="{{ route('password.questions') }}" class="max-w-md">
        @csrf
        <label class="block text-sm font-medium mb-1">One-time PIN</label>
        <input name="otp" inputmode="numeric" maxlength="6" required class="w-full text-center tracking-[0.5em] text-xl px-4 py-3 border rounded-lg">
        <button class="mt-3 w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold">Verify OTP</button>
    </form>
    @endif
</div>
@endsection
