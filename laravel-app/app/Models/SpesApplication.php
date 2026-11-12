<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference_no',
    'student_record_id',
    'student_id',
    'last_name',
    'first_name',
    'middle_name',
    'name_extension',
    'sex',
    'civil_status',
    'birth_date',
    'age',
    'place_of_birth',
    'address',
    'barangay',
    'city_municipality',
    'province',
    'zip_code',
    'contact_number',
    'email',
    'father_name',
    'father_occupation',
    'mother_name',
    'mother_occupation',
    'parents_annual_income',
    'number_of_siblings',
    'education_level',
    'school_name',
    'school_address',
    'course',
    'year_level',
    'general_average',
    'enrollment_status',
    'application_year',
    'work_period',
    'preferred_days',
    'beneficiary_type',
    'previous_spes',
    'previous_year',
    'preferred_assignment',
    'skills',
    'has_birth_certificate',
    'has_enrollment_cert',
    'has_grades',
    'has_indigency',
    'has_photo',
    'application_date',
    'applicant_signature',
    'status',
    'student_can_edit',
    'remarks',
    'spr_data',
    'encoded_by',
    'user_id',
    'public_token',
])]
class SpesApplication extends Model
{
    public const SEXES = [
        'male' => 'Male',
        'female' => 'Female',
    ];

    public const CIVIL_STATUSES = [
        'single' => 'Single',
        'married' => 'Married',
        'widowed' => 'Widowed',
        'separated' => 'Separated',
    ];

    public const EDUCATION_LEVELS = [
        'secondary' => 'Secondary',
        'tertiary' => 'Tertiary',
        'techvoc' => 'Tech-Voc',
        'out_of_school' => 'Out-of-school youth',
    ];

    public const YEAR_LEVELS = [
        'Grade 10',
        'Grade 11',
        'Grade 12',
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
        '5th Year',
    ];

    public const ENROLLMENT_STATUSES = [
        'enrolled' => 'Currently enrolled',
        'intending' => 'Intending to enroll',
    ];

    public const WORK_PERIODS = [
        'summer' => 'Summer',
        'christmas' => 'Christmas',
    ];

    public const BENEFICIARY_TYPES = [
        'student' => 'Student',
        'out_of_school_youth' => 'Out-of-school youth',
    ];

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'employed' => 'Employed',
        'completed' => 'Completed',
    ];

    public const DOCUMENTS = [
        'has_birth_certificate' => 'PSA birth certificate or proof of age',
        'has_enrollment_cert' => 'Certificate of enrollment or registration',
        'has_grades' => 'Report card or copy of grades',
        'has_indigency' => 'Barangay certificate of indigency or parents’ income proof',
        'has_photo' => 'ID photos',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'application_date' => 'date',
            'age' => 'integer',
            'parents_annual_income' => 'decimal:2',
            'general_average' => 'decimal:2',
            'number_of_siblings' => 'integer',
            'application_year' => 'integer',
            'preferred_days' => 'integer',
            'previous_spes' => 'boolean',
            'previous_year' => 'integer',
            'has_birth_certificate' => 'boolean',
            'has_enrollment_cert' => 'boolean',
            'has_grades' => 'boolean',
            'has_indigency' => 'boolean',
            'has_photo' => 'boolean',
            'student_can_edit' => 'boolean',
            'spr_data' => 'array',
        ];
    }

    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ownedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['approved', 'employed', 'completed'], true);
    }

    public function studentMayEdit(): bool
    {
        return (bool) $this->student_can_edit;
    }

    public function fullName(): string
    {
        return User::composeName($this->first_name, $this->middle_name, $this->last_name, $this->name_extension);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved', 'completed' => 'bg-emerald-100 text-emerald-800',
            'employed' => 'bg-sky-100 text-sky-800',
            'under_review' => 'bg-amber-100 text-amber-800',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function documentsAttached(): int
    {
        return collect(array_keys(self::DOCUMENTS))
            ->filter(fn (string $field) => (bool) $this->{$field})
            ->count();
    }

    public static function referenceFor(int $id, int $year): string
    {
        return sprintf('SPR-%d-%04d', $year, $id);
    }

    public function paper(): array
    {
        $paper = [
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'id_no' => $this->student_id,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'civil_status' => $this->civil_status,
            'sex_at_birth' => $this->sex,
            'place_of_birth' => $this->place_of_birth,
            'home_purok' => $this->address,
            'home_barangay' => $this->barangay,
            'home_city' => $this->city_municipality,
            'home_province' => $this->province,
            'home_zip' => $this->zip_code,
            'mobile' => $this->contact_number,
            'email' => $this->email,
            'father_name' => $this->father_name,
            'father_occupation' => $this->father_occupation,
            'mother_name' => $this->mother_name,
            'mother_occupation' => $this->mother_occupation,
            'signature_name' => $this->applicant_signature,
            'priority_1' => $this->course,
            'secondary_school' => $this->school_name,
            'secondary_address' => $this->school_address,
        ];

        foreach ($paper as $key => $value) {
            if ($value === 'N/A') {
                $paper[$key] = '';
            }
        }

        foreach ($this->spr_data ?? [] as $key => $value) {
            if ($value !== null && $value !== '') {
                $paper[$key] = $value;
            }
        }

        return $paper;
    }

    public static function yearLevelFromStudent(int $yearLevel): string
    {
        return match ($yearLevel) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            5 => '5th Year',
            default => '',
        };
    }
}
