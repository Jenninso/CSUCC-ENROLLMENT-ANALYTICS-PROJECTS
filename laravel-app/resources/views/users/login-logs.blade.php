@extends('layouts.app')
@section('title', 'Login Logs')

@section('content')
<p class="text-sm text-gray-500 mb-4">
    @if(auth()->user()->isSuperAdmin())
        Super Administrator view: all sign-in records.
    @elseif(auth()->user()->isAdministrator())
        Administrator view: Administrator and User logs only.
    @else
        User view: User logs only.
    @endif
</p>

<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
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
        <a href="{{ route('login-logs.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg text-center">Reset</a>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Employee ID</th>
                    <th class="px-4 py-3 font-semibold">Full Name</th>
                    <th class="px-4 py-3 font-semibold">Role</th>
                    <th class="px-4 py-3 font-semibold">Time In</th>
                    <th class="px-4 py-3 font-semibold">Time Out</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-t even:bg-gray-50/50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $log->employee_id }}</td>
                    <td class="px-4 py-3">{{ $log->full_name }}</td>
                    <td class="px-4 py-3">{{ $log->role_name }}</td>
                    <td class="px-4 py-3">{{ $log->time_in?->format('M d, Y g:i A') }}</td>
                    <td class="px-4 py-3">{{ $log->time_out?->format('M d, Y g:i A') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No login logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
</div>
@endsection
