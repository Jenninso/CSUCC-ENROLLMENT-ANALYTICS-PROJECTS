@extends('layouts.app')
@section('title', 'User Management')
@section('content')
<div class="mb-4"><a href="{{ route('users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Add User</a></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Role</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
<tbody>@foreach($users as $user)<tr class="border-t"><td class="px-4 py-3">{{ $user->name }}</td><td class="px-4 py-3">{{ $user->email }}</td><td class="px-4 py-3">{{ $user->role?->role_name }}</td><td class="px-4 py-3 capitalize">{{ $user->status }}</td><td class="px-4 py-3"><a href="{{ route('users.edit', $user) }}" class="text-indigo-600 mr-2">Edit</a><form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></td></tr>@endforeach</tbody></table></div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
