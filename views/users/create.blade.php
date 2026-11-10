@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'Add User')
@section('content')
<div class="max-w-lg bg-white rounded-lg shadow-sm p-6">
<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" class="space-y-4">@csrf @if(isset($user)) @method('PUT') @endif
<div><label class="block text-sm mb-1">Name</label><input name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Email</label><input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label><input type="password" name="password" {{ isset($user) ? '' : 'required' }} class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Confirm Password</label><input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2"></div>
<div><label class="block text-sm mb-1">Role</label><select name="role_id" required class="w-full border rounded px-3 py-2">@foreach($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->role_name }}</option>@endforeach</select></div>
<div><label class="block text-sm mb-1">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="active" @selected(old('status', $user->status ?? '') == 'active')>Active</option><option value="inactive" @selected(old('status', $user->status ?? '') == 'inactive')>Inactive</option></select></div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button> <a href="{{ route('users.index') }}">Cancel</a>
</form></div>
@endsection
