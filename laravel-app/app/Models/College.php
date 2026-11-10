<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['college_name', 'college_code'])]
class College extends Model
{
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function enrollmentRecords(): HasMany
    {
        return $this->hasMany(EnrollmentRecord::class);
    }

    public function programs(): HasManyThrough
    {
        return $this->hasManyThrough(Program::class, Department::class);
    }
}
