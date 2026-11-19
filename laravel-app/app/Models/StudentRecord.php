<?php

namespace App\Models;

use App\Services\EnrollmentProjection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'last_name',
    'first_name',
    'middle_name',
    'ext_name',
    'email',
    'gender',
    'age',
    'date_of_birth',
    'mobile',
    'program_id',
    'year_level',
    'section',
    'block',
    'academic_year_id',
    'semester_id',
    'gpa',
    'average_grade',
    'attendance_percent',
    'subjects_count',
    'failed_subjects',
    'units_enrolled',
    'enrolled_status',
    'student_type',
    'academic_standing',
    'scholarship_status',
    'scholar_code',
    'home_address',
    'municipality',
    'province',
    'tuition_status',
    'risk_level',
])]
class StudentRecord extends Model
{
    public const GENDERS = [
        'male' => 'Male',
        'female' => 'Female',
        'unspecified' => 'Unspecified',
    ];

    public const ACADEMIC_STANDINGS = [
        'regular' => 'Regular',
        'warning' => 'Warning',
        'probation' => 'Probation',
        'debarred' => 'Debarred',
    ];

    public const ENROLLED_STATUSES = [
        'enrolled' => 'Enrolled',
        'not_enrolled' => 'Not Enrolled',
        'dropped' => 'Dropped',
        'withdrawn' => 'Withdrawn',
        'on_leave' => 'On Leave',
    ];

    /**
     * Enrollment category for this term (from registrar enrollment_status).
     * Not the same as academic_standing (Regular / Warning / …).
     */
    public const STUDENT_TYPES = [
        'new' => 'New',
        'returning' => 'Old',
        'returnee' => 'Returnee',
        'returnee_shiftee' => 'Returnee-Shiftee',
        'shiftee' => 'Shiftee',
        'transferee' => 'Transferee',
        'withdraw_stop' => 'Withdraw Acad / Stop',
    ];

    public const SCHOLARSHIP_STATUSES = [
        'none' => 'None',
        'academic' => 'Academic Scholar',
        'government' => 'Government Scholar',
        'private' => 'Private Scholar',
    ];

    public const TUITION_STATUSES = [
        'paid' => 'Paid',
        'partial' => 'Partial',
        'unpaid' => 'Unpaid',
        'covered' => 'Scholar-Covered',
    ];

    public const RISK_LEVELS = [
        'low' => 'Low',
        'moderate' => 'Moderate',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'date_of_birth' => 'date',
            'year_level' => 'integer',
            'gpa' => 'decimal:2',
            'average_grade' => 'decimal:2',
            'attendance_percent' => 'decimal:2',
            'subjects_count' => 'integer',
            'failed_subjects' => 'integer',
            'units_enrolled' => 'decimal:1',
        ];
    }

    public function fullName(): string
    {
        $parts = array_filter([
            $this->last_name,
            $this->first_name,
            $this->middle_name,
            $this->ext_name,
        ], fn ($part) => filled($part));

        if ($parts === []) {
            return $this->student_id;
        }

        $last = (string) ($this->last_name ?: '');
        $first = trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->ext_name,
        ], fn ($part) => filled($part))));

        return trim($last.($first !== '' ? ', '.$first : ''));
    }

    protected static function booted(): void
    {
        static::saving(function (StudentRecord $record): void {
            $record->student_id = strtoupper(trim((string) $record->student_id));
            $record->section = strtoupper(trim((string) $record->section));

            $normalized = self::applyNoGradeDropRule($record->getAttributes());
            if (isset($normalized['enrolled_status'])) {
                $record->enrolled_status = $normalized['enrolled_status'];
            }

            $record->risk_level = self::assessRisk($record->getAttributes());
        });

        static::saved(function (StudentRecord $record): void {
            $projection = app(EnrollmentProjection::class);
            $projection->syncFromStudent($record);

            $previous = [
                (int) $record->getOriginal('academic_year_id'),
                (int) $record->getOriginal('semester_id'),
                (int) $record->getOriginal('program_id'),
                (int) $record->getOriginal('year_level'),
            ];
            $current = [
                (int) $record->academic_year_id,
                (int) $record->semester_id,
                (int) $record->program_id,
                (int) $record->year_level,
            ];
            if ($previous[0] > 0 && $previous !== $current) {
                $projection->syncSlice(...$previous);
            }
        });

        static::deleted(function (StudentRecord $record): void {
            app(EnrollmentProjection::class)->syncFromStudent($record);
        });
    }

    /**
     * Dropped happens only when a term has subject load but no usable grades.
     * Usable grades = numeric 1.00–5.00, or PASSED / INC / FAILED style marks.
     * All-DRP / blank terms qualify as Dropped. Terms with real grades must not.
     *
     * Pass has_numeric_grades=true/false (from import) to enforce automatically.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function applyNoGradeDropRule(array $attributes): array
    {
        $subjects = (int) ($attributes['subjects_count'] ?? $attributes['number_of_subjects'] ?? 0);
        $raw = $attributes['has_numeric_grades'] ?? null;
        $hasUsableGrades = match (true) {
            is_bool($raw) => $raw,
            is_numeric($raw) => (int) $raw === 1,
            is_string($raw) => in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'y'], true),
            default => null,
        };

        if ($hasUsableGrades === false && $subjects > 0) {
            $attributes['enrolled_status'] = 'dropped';
        } elseif ($hasUsableGrades === true && ($attributes['enrolled_status'] ?? null) === 'dropped') {
            $attributes['enrolled_status'] = 'enrolled';
        }

        // Dropped terms must not keep leftover GPA/average from other terms.
        if (($attributes['enrolled_status'] ?? null) === 'dropped') {
            $attributes['gpa'] = 5.00;
            $attributes['average_grade'] = 0.00;
        }

        return $attributes;
    }

    /**
     * GPA uses the 1.00 (highest) to 5.00 (failing) scale.
     * Classification uses academic indicators only. Tuition, income,
     * and family circumstances are never used to raise the risk level.
     */
    public static function assessRisk(array $attributes): string
    {
        $gpa = (float) ($attributes['gpa'] ?? 0);
        $failed = (int) ($attributes['failed_subjects'] ?? 0);
        $enrolled = (string) ($attributes['enrolled_status'] ?? 'enrolled');
        $studentType = (string) ($attributes['student_type'] ?? 'new');

        // Attendance is not used — the CEIT dataset does not provide it.
        if (
            in_array($enrolled, ['dropped', 'withdrawn'], true)
            || $studentType === 'withdraw_stop'
            || $failed >= 3
            || $gpa > 3
        ) {
            return 'critical';
        }

        if ($failed >= 2 || $gpa >= 2.75) {
            return 'high';
        }

        if (
            $failed >= 1
            || $gpa >= 2.25
            || in_array($enrolled, ['not_enrolled', 'on_leave'], true)
        ) {
            return 'moderate';
        }

        return 'low';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicGroup(): string
    {
        $code = $this->program?->department?->department_code;
        if ($code === null) {
            return '—';
        }

        return in_array($code, ['IT'], true) ? 'Information Technology' : 'Engineering';
    }

    public function genderLabel(): string
    {
        return self::GENDERS[$this->gender] ?? ucfirst((string) $this->gender);
    }

    public function enrolledLabel(): string
    {
        return self::ENROLLED_STATUSES[$this->enrolled_status] ?? ucfirst((string) $this->enrolled_status);
    }

    public function studentTypeLabel(): string
    {
        return self::STUDENT_TYPES[$this->student_type] ?? ucfirst((string) $this->student_type);
    }

    public function studentTypeBadgeClass(): string
    {
        return match ($this->student_type) {
            'new' => 'bg-emerald-100 text-emerald-800',
            'returning' => 'bg-sky-100 text-sky-800',
            'returnee', 'returnee_shiftee' => 'bg-indigo-100 text-indigo-800',
            'shiftee' => 'bg-amber-100 text-amber-800',
            'transferee' => 'bg-violet-100 text-violet-800',
            'withdraw_stop' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function academicStandingLabel(): string
    {
        if ($this->academic_standing === null || $this->academic_standing === '') {
            return '—';
        }

        return self::ACADEMIC_STANDINGS[$this->academic_standing] ?? ucfirst((string) $this->academic_standing);
    }

    public function academicStandingBadgeClass(): string
    {
        return match ($this->academic_standing) {
            'regular' => 'bg-emerald-100 text-emerald-800',
            'warning' => 'bg-amber-100 text-amber-800',
            'probation' => 'bg-orange-100 text-orange-800',
            'debarred' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function scholarshipLabel(): string
    {
        return self::SCHOLARSHIP_STATUSES[$this->scholarship_status] ?? ucfirst((string) $this->scholarship_status);
    }

    public function tuitionLabel(): string
    {
        return self::TUITION_STATUSES[$this->tuition_status] ?? ucfirst((string) $this->tuition_status);
    }

    public function riskLabel(): string
    {
        return self::RISK_LEVELS[$this->risk_level] ?? ucfirst((string) $this->risk_level);
    }

    public function riskBadgeClass(): string
    {
        return match ($this->risk_level) {
            'low' => 'bg-emerald-100 text-emerald-700',
            'moderate' => 'bg-amber-100 text-amber-800',
            'high' => 'bg-orange-100 text-orange-800',
            'critical' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
