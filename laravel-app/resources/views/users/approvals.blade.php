@extends('layouts.app')
@section('title', 'Registration Approvals')

@section('content')
<p class="text-sm text-gray-500 mb-4">Approve, reject, or block registrations. Accounts are created only after approval.</p>

<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
        <input name="employee_id" value="{{ request('employee_id') }}" placeholder="Employee ID" class="px-3 py-2 border rounded-lg text-sm">
        <input name="email" value="{{ request('email') }}" placeholder="Email" class="px-3 py-2 border rounded-lg text-sm">
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'blocked' => 'Blocked', 'all' => 'All'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status', 'pending') === $value)>{{ $label }}</option>
            @endforeach
        </select>
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
                    <th class="px-4 py-3 font-semibold">Employee ID</th>
                    <th class="px-4 py-3 font-semibold">Full Name</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Submitted</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $row)
                <tr class="border-t even:bg-gray-50/50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $row->employee_id }}</td>
                    <td class="px-4 py-3">
                        <p>{{ $row->fullName() }}</p>
                        @if($row->sex || $row->contact_number)
                            <p class="text-xs text-gray-500">{{ $row->sex }}{{ $row->birthdate ? ' · '.$row->birthdate->age.' yrs' : '' }}{{ $row->contact_number ? ' · '.$row->contact_number : '' }}</p>
                        @endif
                        @if($row->barangay || $row->city_municipality)
                            <p class="text-xs text-gray-500">{{ collect([$row->purok_street, $row->barangay, $row->city_municipality, $row->province, $row->zipcode])->filter()->join(', ') }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $row->email }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $row->created_at?->format('M d, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100">{{ $row->status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($row->status === 'pending')
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('approvals.approve', $row) }}">@csrf
                                <button class="text-xs font-medium text-emerald-600">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('approvals.reject', $row) }}">@csrf
                                <input type="hidden" name="review_notes" value="Rejected">
                                <button class="text-xs font-medium text-amber-600">Reject</button>
                            </form>
                            <form method="POST" action="{{ route('approvals.block', $row) }}">@csrf
                                <input type="hidden" name="review_notes" value="Blocked">
                                <button class="text-xs font-medium text-red-600">Block</button>
                            </form>
                        </div>
                        @else
                            <span class="text-xs text-gray-400">{{ $row->reviewer?->fullName() ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No registration requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $requests->links() }}</div>
</div>
@endsection
