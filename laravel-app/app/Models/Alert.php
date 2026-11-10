<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'program_id',
    'metric',
    'current_value',
    'expected_value',
    'severity',
    'explanation',
    'recommended_action',
    'is_read',
])]
class Alert extends Model
{
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'current_value' => 'decimal:2',
            'expected_value' => 'decimal:2',
        ];
    }
}
