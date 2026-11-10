@extends('layouts.app')
@section('title', 'Logs')

@section('content')
<p class="text-sm text-gray-500 mb-4">All Super Administrator, Administrator, and User activity in one table. Filter by account type to see what each role did.</p>

<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <select name="account_type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All account types</option>
            @foreach($accountTypes as $type)
                <option value="{{ $type }}" @selected(request('account_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <input name="employee_id" value="{{ request('employee_id') }}" placeholder="Employee ID" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date" value="{{ request('date') }}" class="px-3 py-2 border rounded-lg text-sm">
        <select name="month" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">Month</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected(request('month') == $m)>{{ DateTime::createFromFormat('!m', (string) $m)->format('F') }}</option>
            @endfor
        </select>
        <select name="year" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">Year</option>
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
            @endfor
        </select>
        <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Filter</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Date / Time</th>
                    <th class="px-4 py-3 font-semibold">ID No.</th>
                    <th class="px-4 py-3 font-semibold">Full Name</th>
                    <th class="px-4 py-3 font-semibold">Account Type</th>
                    <th class="px-4 py-3 font-semibold">Activity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-t even:bg-gray-50/50">
                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at?->format('M d, Y g:i A') }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $log->employee_id ?? $log->user?->employee_id ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $log->full_name ?? $log->user?->fullName() ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $log->role_name ?? $log->user?->roleName() ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $log->activity() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
</div>
@endsection
