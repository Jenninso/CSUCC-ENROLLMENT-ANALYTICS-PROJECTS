<?php

namespace App\Http\Controllers;

use App\Models\DeleteRequest;
use App\Services\AccountService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class DeleteRequestController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $requests = DeleteRequest::with(['target', 'requester'])->latest()->paginate(10);

        return view('users.delete-requests', compact('requests'));
    }

    public function approve(DeleteRequest $deleteRequest, Request $request, AuditLogger $audit, AccountService $accounts)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($deleteRequest->status === 'pending', 422);

        $target = $deleteRequest->target;
        if ($target && $accounts->isLastActiveSuperAdmin($target)) {
            return back()->with('error', 'You cannot delete the last Super Administrator account.');
        }

        if ($target?->isSuperAdmin()) {
            $confirmed = $accounts->confirmSuperAdminDeletion($request->input('confirm_employee_id'), $target);
            if ($confirmed !== true) {
                return back()
                    ->withInput()
                    ->withErrors(['confirm_employee_id' => $confirmed])
                    ->with('confirm_super_delete_request_id', $deleteRequest->id);
            }
        }

        $deleteRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        $category = $deleteRequest->user_snapshot['account_category']
            ?? ($target?->accountCategoryLabel() ?? 'account');
        $audit->log('delete', 'users', 'Approved deletion of '.$category.' '.($target?->accountLabel() ?? 'account'));
        if ($target) {
            $accounts->deleteUser($target);
        }

        return back()->with('success', 'Account deleted.');
    }

    public function reject(Request $request, DeleteRequest $deleteRequest, AuditLogger $audit)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:500']]);
        $deleteRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? 'Rejected',
        ]);
        $category = $deleteRequest->user_snapshot['account_category']
            ?? ($deleteRequest->target?->accountCategoryLabel() ?? 'account');
        $audit->log('delete_request', 'users', 'Rejected a delete request for '.$category.' '.($deleteRequest->target?->accountLabel() ?? 'an account'));

        return back()->with('success', 'Delete request rejected.');
    }
}
