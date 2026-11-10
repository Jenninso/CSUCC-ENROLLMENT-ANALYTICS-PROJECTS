@extends('layouts.app')
@section('title', 'Privileges')

@section('content')
<div class="max-w-3xl">
    <p class="text-sm text-gray-500 mb-4">Assign access control for {{ $user->employee_id }} — {{ $user->fullName() }} ({{ $user->roleName() }}).</p>
    <form method="POST" action="{{ route('users.privileges.update', $user) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf
        @foreach($permissions->groupBy('module') as $module => $group)
            <fieldset>
                <legend class="text-sm font-semibold text-gray-800 capitalize mb-3">{{ $module ?: 'General' }}</legend>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach($group as $permission)
                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assigned, true)) class="mt-0.5 rounded border-gray-300 text-indigo-600">
                        <span>{{ $permission->name }} <span class="text-xs text-gray-400">{{ $permission->key }}</span></span>
                    </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <div class="flex gap-3">
            <button class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg">Save privileges</button>
            <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg">Back</a>
        </div>
    </form>
</div>
@endsection
