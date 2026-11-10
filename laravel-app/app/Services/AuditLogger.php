<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(string $action, string $module, ?string $details = null, ?Request $request = null, ?User $actor = null): void
    {
        $request ??= request();
        $actor ??= $request?->user();
        $actor?->loadMissing('role');

        AuditLog::create([
            'user_id' => $actor?->id,
            'employee_id' => $actor?->employee_id,
            'full_name' => $actor?->fullName() ?: ($actor?->email),
            'role_name' => $actor?->roleName(),
            'action' => $action,
            'module' => $module,
            'ip_address' => $request?->ip(),
            'details' => $details,
        ]);
    }
}
