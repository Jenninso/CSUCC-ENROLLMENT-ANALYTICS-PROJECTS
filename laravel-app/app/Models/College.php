<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['college_name', 'college_code'])]
class College extends Model
{
    /**
     * Colleges available in filter dropdowns — CEIT only (system scope).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function forFilters()
    {
        $code = strtoupper((string) config('scope.college_code', 'CEIT'));

        return static::query()
            ->whereRaw('UPPER(college_code) = ?', [$code])
            ->orderBy('college_name')
            ->get();
    }

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
