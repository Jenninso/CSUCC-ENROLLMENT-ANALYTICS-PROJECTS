<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginLogService
{
    public function timeIn(User $user, Request $request): LoginLog
    {
        LoginLog::query()
            ->where('user_id', $user->id)
            ->whereNull('time_out')
            ->update(['time_out' => now()]);

        $user->loadMissing('role');

        $log = LoginLog::create([
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'full_name' => $user->fullName(),
            'role_name' => $user->roleName(),
            'time_in' => now(),
            'ip_address' => $request->ip(),
        ]);

        $request->session()->put('login_log_id', $log->id);

        return $log;
    }

    public function timeOut(?Request $request = null): void
    {
        $request ??= request();
        $id = $request?->session()->pull('login_log_id');
        if (! $id) {
            $user = $request?->user();
            if ($user) {
                LoginLog::query()
                    ->where('user_id', $user->id)
                    ->whereNull('time_out')
                    ->latest('time_in')
                    ->first()
                    ?->update(['time_out' => now()]);
            }

            return;
        }

        LoginLog::query()->where('id', $id)->whereNull('time_out')->update(['time_out' => now()]);
    }
}
