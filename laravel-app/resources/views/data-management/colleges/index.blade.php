@extends('layouts.app')
@section('title', 'Colleges')
@section('subtitle', 'Manage college records.')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('data-management.colleges.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add College
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">College Name</th>
                    <th class="px-4 py-3 font-semibold">Dean / Head</th>
                    <th class="px-4 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($colleges ?? [] as $item)
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $item->college_code ?? $item->code ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium">{{ $item->college_name }}</td>
                    <td class="px-4 py-3">{{ $item->dean_name ?? $item->head ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('data-management.colleges.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('data-management.colleges.destroy', $item) }}" onsubmit="return confirm('Delete this college?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No colleges found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($colleges) && method_exists($colleges, 'links'))
    <div class="px-4 py-3 border-t">{{ $colleges->links() }}</div>
    @endif
</div>
@endsection
