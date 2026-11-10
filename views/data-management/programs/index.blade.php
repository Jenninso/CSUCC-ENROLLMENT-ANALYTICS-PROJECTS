@extends('layouts.app')
@section('title', 'Programs')
@section('content')
<div class="mb-4"><a href="{{ route('programs.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Add Program</a></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Department</th><th class="px-4 py-3 text-left">Level</th><th class="px-4 py-3">Actions</th></tr></thead>
<tbody>@foreach($programs as $item)<tr class="border-t"><td class="px-4 py-3">{{ $item->program_name }}</td><td class="px-4 py-3">{{ $item->program_code }}</td><td class="px-4 py-3">{{ $item->department?->department_name }}</td><td class="px-4 py-3">{{ $item->degree_level }}</td><td class="px-4 py-3"><a href="{{ route('programs.edit', $item) }}" class="text-indigo-600 mr-2">Edit</a><form action="{{ route('programs.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></td></tr>@endforeach</tbody></table></div>
<div class="mt-4">{{ $programs->links() }}</div>
@endsection
