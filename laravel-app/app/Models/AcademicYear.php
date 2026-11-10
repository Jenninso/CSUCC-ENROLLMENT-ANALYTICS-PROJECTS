<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year', 'start_date', 'end_date', 'status'])]
class AcademicYear extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function enrollmentRecords(): HasMany
    {
        return $this->hasMany(EnrollmentRecord::class);
    }
}
