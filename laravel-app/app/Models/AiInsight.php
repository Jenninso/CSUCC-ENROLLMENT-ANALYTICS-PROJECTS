<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'program_id',
    'insight_type',
    'title',
    'description',
    'situation',
    'evidence',
    'interpretation',
    'forecast_note',
    'severity',
    'supporting_metric',
    'recommendation',
    'confidence',
    'status',
])]
class AiInsight extends Model
{
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
