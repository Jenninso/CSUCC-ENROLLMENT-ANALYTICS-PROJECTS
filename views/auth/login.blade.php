@extends('layouts.guest')
@section('content')
<div class="bg-white rounded-xl shadow-xl p-8">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Enrollment Analytics</h1>
        <p class="text-sm text-gray-500 mt-1">AI-Driven Decision Support System</p>
    </div>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        @if($errors->any())
            <div class="text-red-600 text-sm">{{ $errors->first() }}</div>
        @endif
        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 font-medium">Sign In</button>
    </form>
    <p class="text-xs text-gray-400 text-center mt-4">Default: admin@enrollment.com / password</p>
</div>
@endsection
