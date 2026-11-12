<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'student_id',
    'student_record_id',
    'action_type',
    'subject_name',
    'from_program',
    'to_program',
    'previous_school',
    'reason',
    'effective_date',
    'status',
    'staff_remarks',
    'reviewed_by',
])]
class StudentStatusReason extends Model
{
    public const ACTIONS = [
        'shift_program' => 'Shifted to another program',
        'transferee' => 'Transferred in from another school',
        'transfer_out' => 'Transferred out',
        'withdraw_subject' => 'Withdrew a subject',
        'drop_subject' => 'Dropped a subject',
        'withdraw_academic' => 'Withdrew from the term or stopped',
    ];

    public const STATUSES = [
        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'noted' => 'Noted',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action_type] ?? ucfirst(str_replace('_', ' ', (string) $this->action_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isOpen(): bool
    {
        return $this->status === 'submitted';
    }
}
