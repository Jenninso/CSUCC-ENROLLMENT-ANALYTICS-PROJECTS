@extends('layouts.app')
@section('title', 'Edit Account')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                <input value="{{ $user->employee_id }}" readonly class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 text-gray-600">
                <input type="hidden" name="employee_id" value="{{ $user->employee_id }}">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username (used to sign in)</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                       class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="jennel.paracuelles">
                @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1">This is what the user types on the login screen.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email (OTP &amp; notifications)</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border rounded-lg text-sm">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs mt-1 {{ $user->isInstitutionalAccount() ? 'text-emerald-700' : 'text-slate-600' }}">
                    Currently recognized as <span class="font-medium">{{ $user->accountCategoryLabel() }}</span>.
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-gray-400 font-normal">(optional)</span></label>
                <input name="address" value="{{ old('address', $user->address) }}" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg text-sm">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->isSuperAdmin())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border rounded-lg text-sm">
                    @foreach(['active' => 'Active', 'inactive' => 'Deactivated', 'blocked' => 'Blocked'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex gap-3 pt-2">
                <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Update</button>
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
