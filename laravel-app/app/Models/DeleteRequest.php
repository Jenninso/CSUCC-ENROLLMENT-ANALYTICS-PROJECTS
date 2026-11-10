<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'target_user_id',
    'requested_by',
    'reason',
    'user_snapshot',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
])]
class DeleteRequest extends Model
{
    protected function casts(): array
    {
        return [
            'user_snapshot' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
