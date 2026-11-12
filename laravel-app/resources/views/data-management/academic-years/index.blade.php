@extends('layouts.app')
@section('title', 'Academic Years')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage academic year records.</p>
    <a href="{{ route('data-management.academic-years.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Academic Year
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Year Name</th>
                    <th class="px-4 py-3 font-semibold">Start Date</th>
                    <th class="px-4 py-3 font-semibold">End Date</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($academicYears ?? [] as $item)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium">{{ $item->year_name ?? $item->academic_year }}</td>
                    <td class="px-4 py-3">{{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('M d, Y') : '—' }}</td>
                    <td class="px-4 py-3">{{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('M d, Y') : '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ ($item->status ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $item->status ?? 'inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('data-management.academic-years.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('data-management.academic-years.destroy', $item) }}" onsubmit="return confirm('{{ ($item->status ?? '') === 'active' ? 'Delete this academic year?' : 'Delete this inactive academic year? Its semesters and enrollment counts will be removed.' }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No academic years found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($academicYears) && method_exists($academicYears, 'links'))
    <div class="px-4 py-3 border-t">{{ $academicYears->links() }}</div>
    @endif
</div>
@endsection
