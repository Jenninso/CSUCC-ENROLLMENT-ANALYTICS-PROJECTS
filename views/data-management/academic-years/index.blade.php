@extends('layouts.app')
@section('title', 'Academic Years')
@section('content')
<div class="mb-4"><a href="{{ route('academic-years.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Add Academic Year</a></div>
<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Year</th><th class="px-4 py-3 text-left">Start</th><th class="px-4 py-3 text-left">End</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
<tbody>@foreach($academicYears as $item)<tr class="border-t"><td class="px-4 py-3">{{ $item->academic_year }}</td><td class="px-4 py-3">{{ $item->start_date->format('M d, Y') }}</td><td class="px-4 py-3">{{ $item->end_date->format('M d, Y') }}</td><td class="px-4 py-3 capitalize">{{ $item->status }}</td><td class="px-4 py-3"><a href="{{ route('academic-years.edit', $item) }}" class="text-indigo-600 mr-2">Edit</a><form action="{{ route('academic-years.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></td></tr>@endforeach</tbody></table></div>
<div class="mt-4">{{ $academicYears->links() }}</div>
@endsection
