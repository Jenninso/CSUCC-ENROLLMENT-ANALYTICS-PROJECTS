<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['question', 'is_active'])]
class SecretQuestion extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserSecretAnswer::class);
    }
}
