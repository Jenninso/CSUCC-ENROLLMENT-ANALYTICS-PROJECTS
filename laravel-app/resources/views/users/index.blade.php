@extends('layouts.app')
@section('title', 'User Management')
@section('subtitle', 'Staff accounts, roles, and access for this campus system.')

@section('content')
@php $me = auth()->user(); @endphp
@if($me?->canAccess('users.create_admin'))
<div class="flex justify-end mb-6">
    <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        Add Super Admin / Admin / User
    </a>
</div>
@endif

<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input name="employee_id" value="{{ request('employee_id') }}" placeholder="Employee ID" class="px-3 py-2 border rounded-lg text-sm">
        <select name="role_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->role_name }}</option>
            @endforeach
        </select>
        <select name="account_category" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All account kinds</option>
            <option value="institutional" @selected(request('account_category') === 'institutional')>Institutional Account</option>
            <option value="personal" @selected(request('account_category') === 'personal')>Personal Account</option>
        </select>
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All statuses</option>
            @foreach(['active' => 'Active', 'inactive' => 'Deactivated'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Filter</button>
        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg text-center">Reset</a>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm" x-data="{ deleteId: @js(session('open_delete_id')), blockId: null }" @block-account.window="blockId = $event.detail" @delete-request.window="deleteId = $event.detail">
    <div class="overflow-x-auto overflow-y-visible rounded-t-lg">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Employee ID</th>
                    <th class="px-4 py-3 font-semibold">Full Name</th>
                    <th class="px-4 py-3 font-semibold">Username</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Account</th>
                    <th class="px-4 py-3 font-semibold">Role</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Presence</th>
                    <th class="px-4 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php $locked = $user->isSuperAdmin() && ! $me->isSuperAdmin(); @endphp
                <tr class="border-t hover:bg-indigo-50/40 even:bg-gray-50/50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $user->employee_id }}</td>
                    <td class="px-4 py-3 font-medium">{{ $user->fullName() }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $user->username }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->isInstitutionalAccount() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $user->isInstitutionalAccount() ? 'Institutional' : 'Personal' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $user->role->role_name ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($user->status === 'blocked' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $user->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->isOnline() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $user->presenceLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center align-top">
                        @if($locked)
                            <span class="text-xs text-gray-400">Restricted</span>
                        @else
                        <div
                            class="relative inline-block text-left"
                            x-data="{
                                open: false,
                                panelStyle: '',
                                toggle(event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (this.open) {
                                        this.open = false;
                                        return;
                                    }
                                    window.dispatchEvent(new CustomEvent('close-actions'));
                                    const r = event.currentTarget.getBoundingClientRect();
                                    const spaceBelow = window.innerHeight - r.bottom;
                                    const openUp = spaceBelow < 220 && r.top > spaceBelow;
                                    const left = Math.max(8, Math.min(r.right - 208, window.innerWidth - 216));
                                    this.panelStyle = openUp
                                        ? `position:fixed;left:${left}px;bottom:${window.innerHeight - r.top + 4}px;z-index:9999;`
                                        : `position:fixed;left:${left}px;top:${r.bottom + 4}px;z-index:9999;`;
                                    this.open = true;
                                }
                            }"
                            @close-actions.window="open = false"
                            @keydown.escape.window="open = false"
                            x-ref="root"
                        >
                            <button
                                type="button"
                                @click="toggle($event)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                :aria-expanded="open"
                                aria-haspopup="true"
                            >
                                Actions
                                <svg class="w-3.5 h-3.5 text-gray-500 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <template x-teleport="body">
                                <div
                                    x-show="open"
                                    x-transition
                                    @click.outside="open = false"
                                    x-ref="panel"
                                    data-user-actions-panel
                                    :style="panelStyle"
                                    class="w-52 rounded-lg border border-gray-200 dark-panel py-1 shadow-2xl text-left"
                                    style="background: var(--theme-panel); color: var(--theme-text); z-index: 9999;"
                                >
                                    <div class="px-3 py-2 border-b border-gray-100">
                                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $user->fullName() }}</p>
                                        <p class="text-[10px] font-mono text-gray-500">{{ $user->employee_id }}</p>
                                    </div>
                                    <a href="{{ route('users.edit', $user) }}" class="block px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-50" @click="open = false">Edit</a>
                                    @if($me->canAccess('users.privileges'))
                                        <a href="{{ route('users.privileges', $user) }}" class="block px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50" @click="open = false">Privileges</a>
                                    @endif
                                    @if($me->isSuperAdmin() && $user->id !== $me->id)
                                        @if($user->isInactive())
                                            <form method="POST" action="{{ route('users.activate', $user) }}">@csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Activate</button>
                                            </form>
                                        @elseif($user->isSuperAdmin() && $user->isActive())
                                            <form method="POST" action="{{ route('users.deactivate', $user) }}">@csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-xs font-medium text-amber-800 hover:bg-amber-50">Deactivate</button>
                                            </form>
                                        @endif
                                    @endif
                                    @if($user->isBlocked())
                                        <form method="POST" action="{{ route('users.unblock', $user) }}">@csrf
                                            <button type="submit" class="w-full text-left px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Unblock</button>
                                        </form>
                                    @elseif($user->id !== $me->id)
                                        <button type="button" @click="open = false; $dispatch('block-account', {{ $user->id }})" class="w-full text-left px-3 py-2 text-xs font-medium text-amber-700 hover:bg-amber-50">Block</button>
                                    @endif
                                    @if($user->id !== $me->id)
                                        <div class="my-1 border-t border-gray-100"></div>
                                        @if($me->isSuperAdmin())
                                            <a href="{{ route('users.confirm-delete', $user) }}" class="block px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50" @click="open = false">Delete</a>
                                        @else
                                            <button type="button" @click="open = false; $dispatch('delete-request', {{ $user->id }})" class="w-full text-left px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Request delete</button>
                                        @endif
                                    @endif
                                </div>
                            </template>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No accounts match the filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $users->links() }}</div>

    @foreach($users as $user)
        @if(! $user->isSuperAdmin() || $me->isSuperAdmin())
        <div x-show="blockId === {{ $user->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <form method="POST" action="{{ route('users.block', $user) }}" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-800">Block {{ $user->employee_id }}</h3>
                <textarea name="reason" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Reason (optional)"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="blockId = null" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Cancel</button>
                    <button class="px-4 py-2 text-sm bg-amber-600 text-white rounded-lg">Block account</button>
                </div>
            </form>
        </div>
        @if(! $me->isSuperAdmin() && $user->id !== $me->id)
        <div x-show="deleteId === {{ $user->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md space-y-4">
                @csrf @method('DELETE')
                <h3 class="font-semibold text-gray-800">Request deletion</h3>
                <p class="text-sm text-gray-500">This is sent to the Super Administrator with the account snapshot and your reason. Confirm with your email and password before sending.</p>
                <p class="text-xs bg-slate-50 border rounded-lg p-3">
                    {{ $user->employee_id }} · {{ $user->fullName() }} · {{ $user->email }} · {{ $user->roleName() }}
                    <span class="block mt-1 font-medium {{ $user->isInstitutionalAccount() ? 'text-emerald-700' : 'text-slate-700' }}">{{ $user->accountCategoryLabel() }}</span>
                </p>
                <textarea name="reason" rows="4" required class="w-full border rounded-lg px-3 py-2 text-sm @error('reason') border-red-500 @enderror" placeholder="Reason to delete">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-xs text-red-600 -mt-2">{{ $message }}</p> @enderror
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your email</label>
                    <input type="email" name="confirm_email" value="{{ old('confirm_email') }}" required autocomplete="username"
                        class="w-full border rounded-lg px-3 py-2 text-sm @error('confirm_email') border-red-500 @enderror"
                        placeholder="{{ $me->email }}">
                    @error('confirm_email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your password</label>
                    <input type="password" name="confirm_password" required autocomplete="current-password"
                        class="w-full border rounded-lg px-3 py-2 text-sm @error('confirm_password') border-red-500 @enderror"
                        placeholder="Current password">
                    @error('confirm_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteId = null" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Cancel</button>
                    <button class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">Send to Super Admin</button>
                </div>
            </form>
        </div>
        @endif
        @endif
    @endforeach
</div>
@endsection
