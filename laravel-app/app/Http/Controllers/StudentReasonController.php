<?php

namespace App\Http\Controllers;

use App\Models\StudentRecord;
use App\Models\StudentStatusReason;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentReasonController extends Controller
{
    public function index(Request $request)
    {
        $reasons = StudentStatusReason::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(10);

        return view('student.reasons-index', ['reasons' => $reasons]);
    }

    public function create()
    {
        return view('student.reasons-form');
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $reason = StudentStatusReason::create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'student_id' => $request->user()->studentNumber(),
            'status' => 'submitted',
        ]);
        $audit->log('create', 'student-reasons', 'Student submitted a '.$reason->actionLabel().' reason.');

        return redirect()->route('student.reasons.index')->with('success', 'Your reason was submitted for review.');
    }

    public function edit(Request $request, StudentStatusReason $reason)
    {
        $this->authorizeOwner($request, $reason);
        if (! $reason->isOpen()) {
            return redirect()->route('student.reasons.index')->with('error', 'This reason was already reviewed and can no longer be edited.');
        }

        return view('student.reasons-form', ['reason' => $reason]);
    }

    public function update(Request $request, StudentStatusReason $reason, AuditLogger $audit)
    {
        $this->authorizeOwner($request, $reason);
        if (! $reason->isOpen()) {
            return redirect()->route('student.reasons.index')->with('error', 'This reason can no longer be edited.');
        }

        $reason->update($this->validated($request));
        $audit->log('update', 'student-reasons', 'Student updated a '.$reason->actionLabel().' reason.');

        return redirect()->route('student.reasons.index')->with('success', 'Your reason was updated.');
    }

    public function destroy(Request $request, StudentStatusReason $reason, AuditLogger $audit)
    {
        $this->authorizeOwner($request, $reason);
        if (! $reason->isOpen()) {
            return redirect()->route('student.reasons.index')->with('error', 'Reviewed reasons stay on file.');
        }

        $reason->delete();
        $audit->log('delete', 'student-reasons', 'Student removed a status reason.');

        return redirect()->route('student.reasons.index')->with('success', 'Reason removed.');
    }

    public function staffIndex(Request $request)
    {
        $query = StudentStatusReason::query()->with('user');
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->query('action_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('student_id', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('subject_name', 'like', "%{$search}%");
            });
        }

        return view('student.reasons-staff', [
            'reasons' => $query->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function review(Request $request, StudentStatusReason $reason, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(StudentStatusReason::STATUSES))],
            'staff_remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason->update([
            'status' => $data['status'],
            'staff_remarks' => $data['staff_remarks'] ?? null,
            'reviewed_by' => $request->user()->id,
        ]);
        $audit->log('update', 'student-reasons', 'Reviewed status reason #'.$reason->id.' for '.$reason->student_id.'.');

        return back()->with('success', 'Reason updated.');
    }

    private function authorizeOwner(Request $request, StudentStatusReason $reason): void
    {
        abort_unless((int) $reason->user_id === (int) $request->user()->id, 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'action_type' => ['required', Rule::in(array_keys(StudentStatusReason::ACTIONS))],
            'subject_name' => ['nullable', 'string', 'max:150', 'required_if:action_type,withdraw_subject', 'required_if:action_type,drop_subject'],
            'from_program' => ['nullable', 'string', 'max:150', 'required_if:action_type,shift_program', 'required_if:action_type,transfer_out'],
            'to_program' => ['nullable', 'string', 'max:150', 'required_if:action_type,shift_program'],
            'previous_school' => ['nullable', 'string', 'max:180', 'required_if:action_type,transferee'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $studentId = $request->user()->studentNumber();
        $data['student_record_id'] = StudentRecord::query()->where('student_id', $studentId)->latest('id')->value('id');
        foreach (['subject_name', 'from_program', 'to_program', 'previous_school'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? '')) ?: null;
        }

        return $data;
    }
}
