<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'first_name',
    'middle_name',
    'last_name',
    'extension_name',
    'birthdate',
    'sex',
    'email',
    'purok_street',
    'barangay',
    'city_municipality',
    'province',
    'zipcode',
    'contact_number',
    'password_hash',
    'secret_answers',
    'status',
    'otp_verified_at',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
])]
class RegistrationRequest extends Model
{
    protected function casts(): array
    {
        return [
            'secret_answers' => 'array',
            'otp_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'birthdate' => 'date',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fullName(): string
    {
        return User::composeName($this->first_name, $this->middle_name, $this->last_name, $this->extension_name);
    }
}
