@extends('layouts.app')
@section('title', 'Delete Requests')

@section('content')
<p class="text-sm text-gray-500 mb-4">Administrator delete requests include the reason and a full account snapshot. Only Super Administrators can approve the actual deletion.</p>

<div class="space-y-4" x-data="{ approveId: {{ session('confirm_super_delete_request_id') ? (int) session('confirm_super_delete_request_id') : 'null' }}, confirmText: @js(old('confirm_employee_id', '')) }">
    @forelse($requests as $row)
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
                <p class="font-semibold text-gray-800">{{ $row->user_snapshot['employee_id'] ?? $row->target?->employee_id }} — {{ $row->user_snapshot['full_name'] ?? $row->target?->fullName() }}</p>
                <p class="text-xs text-gray-500">Requested by {{ $row->requester?->fullName() }} · {{ $row->created_at?->format('M d, Y g:i A') }} · {{ $row->status }}</p>
                @php
                    $categoryLabel = $row->user_snapshot['account_category'] ?? $row->target?->accountCategoryLabel();
                    $isInstitutional = str_contains(strtolower((string) $categoryLabel), 'institutional')
                        || $row->target?->isInstitutionalAccount();
                @endphp
                @if($categoryLabel)
                    <p class="mt-1">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $isInstitutional ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $categoryLabel }}</span>
                    </p>
                @endif
            </div>
            @if($row->status === 'pending')
            <div class="flex gap-2">
                @if($row->target?->isSuperAdmin())
                    <button type="button" @click="approveId = {{ $row->id }}; confirmText = ''" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg">Approve delete</button>
                @else
                <form method="POST" action="{{ route('delete-requests.approve', $row) }}">@csrf
                    <button class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg" onclick="return confirm('Permanently delete this account?')">Approve delete</button>
                </form>
                @endif
                <form method="POST" action="{{ route('delete-requests.reject', $row) }}">@csrf
                    <button class="px-3 py-1.5 text-xs bg-gray-100 rounded-lg">Reject</button>
                </form>
            </div>
            @endif
        </div>
        <p class="text-sm text-gray-700 mb-3"><span class="font-medium">Reason:</span> {{ $row->reason }}</p>
        <dl class="grid sm:grid-cols-2 gap-2 text-sm bg-slate-50 rounded-lg p-3">
            @foreach($row->user_snapshot ?? [] as $key => $value)
                <div><dt class="text-xs uppercase text-gray-400">{{ str_replace('_', ' ', $key) }}</dt><dd>{{ is_scalar($value) ? $value : json_encode($value) }}</dd></div>
            @endforeach
        </dl>
    </div>
    @if($row->status === 'pending' && $row->target?->isSuperAdmin())
    <div x-show="approveId === {{ $row->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" action="{{ route('delete-requests.approve', $row) }}" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md space-y-4">
            @csrf
            <h3 class="font-semibold text-gray-800">Confirm Super Administrator deletion</h3>
            <p class="text-sm text-gray-600">Type the Employee ID to permanently delete this Super Administrator. This cannot be undone.</p>
            <p class="text-xs bg-red-50 border border-red-100 text-red-800 rounded-lg p-3">{{ $row->target->employee_id }} · {{ $row->target->fullName() }}</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="font-mono">{{ $row->target->employee_id }}</span> to confirm</label>
                <input type="text" name="confirm_employee_id" x-model="confirmText" autocomplete="off" class="w-full border rounded-lg px-3 py-2 text-sm font-mono">
                @error('confirm_employee_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="approveId = null; confirmText = ''" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit"
                    :disabled="confirmText.trim().toUpperCase() !== @js(strtoupper($row->target->employee_id))"
                    class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white disabled:bg-red-200 disabled:text-red-400 disabled:cursor-not-allowed">Permanently delete</button>
            </div>
        </form>
    </div>
    @endif
    @empty
    <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400">No delete requests.</div>
    @endforelse
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection
