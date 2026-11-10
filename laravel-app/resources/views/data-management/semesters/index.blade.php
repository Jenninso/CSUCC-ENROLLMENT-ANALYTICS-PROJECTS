@extends('layouts.app')
@section('title', 'Semesters')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage semester records.</p>
    <a href="{{ route('data-management.semesters.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Semester
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Semester Name</th>
                    <th class="px-4 py-3 font-semibold">Academic Year</th>
                    <th class="px-4 py-3 font-semibold">Order</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($semesters ?? [] as $item)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium">{{ $item->semester_name }}</td>
                    <td class="px-4 py-3">{{ $item->academicYear->year_name ?? $item->academicYear->academic_year ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->semester_order ?? $item->order ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ ($item->is_active ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ($item->is_active ?? false) ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('data-management.semesters.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('data-management.semesters.destroy', $item) }}" onsubmit="return confirm('Delete this semester?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No semesters found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($semesters) && method_exists($semesters, 'links'))
    <div class="px-4 py-3 border-t">{{ $semesters->links() }}</div>
    @endif
</div>
@endsection
