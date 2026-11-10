@extends('layouts.app')
@section('title', 'Semesters')
@section('content')
<div class="mb-4"><a href="{{ route('semesters.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Add Semester</a></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Semester</th><th class="px-4 py-3 text-left">Academic Year</th><th class="px-4 py-3">Actions</th></tr></thead>
<tbody>@foreach($semesters as $item)<tr class="border-t"><td class="px-4 py-3">{{ $item->semester_name }}</td><td class="px-4 py-3">{{ $item->academicYear?->academic_year }}</td><td class="px-4 py-3"><a href="{{ route('semesters.edit', $item) }}" class="text-indigo-600 mr-2">Edit</a><form action="{{ route('semesters.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></td></tr>@endforeach</tbody></table></div>
<div class="mt-4">{{ $semesters->links() }}</div>
@endsection
