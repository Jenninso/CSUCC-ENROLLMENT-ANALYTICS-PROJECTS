<?php

namespace App\Http\Controllers;

use App\Models\RegistrationRequest;
use App\Services\AccountService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class RegistrationApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $requests = RegistrationRequest::query()
            ->with('reviewer')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', 'like', '%'.strtoupper($v).'%'))
            ->when($request->email, fn ($q, $v) => $q->where('email', 'like', '%'.strtolower($v).'%'))
            ->when($request->date, fn ($q, $v) => $q->whereDate('created_at', $v))
            ->when($request->month, fn ($q, $v) => $q->whereMonth('created_at', $v)->whereYear('created_at', $request->year ?: now()->year))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.approvals', compact('requests'));
    }

    public function approve(RegistrationRequest $registration, AccountService $accounts, AuditLogger $audit)
    {
        abort_unless($registration->status === 'pending', 422);
        $accounts->approveRegistration($registration, auth()->user());
        $audit->log('approve', 'registration', 'Approved user registration '.$registration->employee_id.' ('.$registration->fullName().')');

        return back()->with('success', 'Registration approved. The user may now sign in.');
    }

    public function reject(Request $request, RegistrationRequest $registration, AuditLogger $audit)
    {
        abort_unless($registration->status === 'pending', 422);
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:500']]);
        $registration->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? 'Rejected',
        ]);
        $audit->log('reject', 'registration', 'Rejected user registration '.$registration->employee_id.' ('.$registration->fullName().')');

        return back()->with('success', 'Registration rejected.');
    }

    public function block(Request $request, RegistrationRequest $registration, AuditLogger $audit)
    {
        abort_unless($registration->status === 'pending', 422);
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:500']]);
        $registration->update([
            'status' => 'blocked',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? 'Blocked',
        ]);
        $audit->log('block', 'registration', 'Blocked user registration '.$registration->employee_id.' ('.$registration->fullName().')');

        return back()->with('success', 'Registration blocked.');
    }
}
