<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $logs = LoginLog::query()
            ->when($user->isAdministrator() && ! $user->isSuperAdmin(), fn ($q) => $q->whereIn('role_name', ['Administrator', 'User']))
            ->when($user->isUserRole(), fn ($q) => $q->where('role_name', 'User'))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', 'like', '%'.strtoupper($v).'%'))
            ->when($request->date, fn ($q, $v) => $q->whereDate('time_in', $v))
            ->when($request->month, fn ($q, $v) => $q->whereMonth('time_in', $v)->whereYear('time_in', $request->year ?: now()->year))
            ->latest('time_in')
            ->paginate(15)
            ->withQueryString();

        return view('users.login-logs', compact('logs'));
    }
}
