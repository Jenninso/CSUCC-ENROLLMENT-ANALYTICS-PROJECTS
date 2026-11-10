<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'email',
    'purpose',
    'otp_hash',
    'expires_at',
    'attempts',
    'max_attempts',
    'last_sent_at',
    'verified_at',
    'invalidated_at',
    'ip_address',
])]
#[Hidden(['otp_hash'])]
class OtpVerification extends Model
{
    protected $table = 'otp_verifications';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isInvalidated(): bool
    {
        return $this->invalidated_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function isActive(): bool
    {
        return ! $this->isVerified() && ! $this->isInvalidated() && ! $this->isExpired() && ! $this->isLocked();
    }
}
