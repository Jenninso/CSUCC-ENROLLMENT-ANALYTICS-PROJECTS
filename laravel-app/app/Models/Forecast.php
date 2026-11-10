<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'program_id',
    'college_id',
    'forecast_period',
    'predicted_enrollment',
    'lower_bound',
    'upper_bound',
    'model_used',
    'accuracy_metric',
])]
class Forecast extends Model
{
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }
}
