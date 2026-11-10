@extends('layouts.guest')
@section('title', 'Forgot Password')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8">
        <p class="text-xs text-emerald-700 font-medium mb-1 text-center">Step 1 of 3</p>
        <h2 class="text-xl font-semibold mb-2 text-center">Forgot password</h2>
        <p class="text-sm text-gray-500 mb-4 text-center">Enter your Employee ID and confirm. Your name will appear when the ID is found.</p>
    @if($errors->any())<p class="text-sm text-red-600 mb-3">{{ $errors->first() }}</p>@endif
    <form method="POST" action="{{ route('password.request') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
            <input id="employee_id" name="employee_id" value="{{ old('employee_id') }}" required autocomplete="off" class="w-full px-4 py-2.5 border rounded-lg" placeholder="EMP-0001">
        </div>
        <div id="name-box" class="hidden bg-slate-50 border rounded-lg px-4 py-3 text-sm">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Account name</p>
            <p id="account-name" class="font-semibold text-gray-800"></p>
            <p id="account-role" class="text-xs text-gray-500"></p>
        </div>
        <p id="lookup-error" class="hidden text-sm text-red-600">No account was found for that Employee ID.</p>
        <button class="w-full bg-[#0b5d3b] text-white py-2.5 rounded-lg font-semibold">Confirm</button>
        <p class="text-sm text-center"><a href="{{ route('login') }}" class="text-emerald-700">Back to sign in</a></p>
    </form>
</div>
<script>
    const input = document.getElementById('employee_id');
    const nameBox = document.getElementById('name-box');
    const nameEl = document.getElementById('account-name');
    const roleEl = document.getElementById('account-role');
    const errorEl = document.getElementById('lookup-error');
    const lookupUrl = @json(route('password.lookup'));
    let timer = null;

    function lookup() {
        const id = input.value.trim();
        if (!id) {
            nameBox.classList.add('hidden');
            errorEl.classList.add('hidden');
            return;
        }
        fetch(lookupUrl + '?employee_id=' + encodeURIComponent(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).then(data => {
            if (data.found) {
                nameEl.textContent = data.name;
                roleEl.textContent = data.role || '';
                nameBox.classList.remove('hidden');
                errorEl.classList.add('hidden');
            } else {
                nameBox.classList.add('hidden');
                errorEl.classList.remove('hidden');
            }
        }).catch(() => {});
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(lookup, 400);
    });
    input.addEventListener('blur', lookup);
    if (input.value.trim()) lookup();
</script>
@endsection
