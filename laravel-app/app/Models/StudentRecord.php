<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'gender',
    'age',
    'program_id',
    'year_level',
    'section',
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
    'scholarship_status',
    'tuition_status',
    'risk_level',
])]
class StudentRecord extends Model
{
    public const GENDERS = [
        'male' => 'Male',
        'female' => 'Female',
    ];

    public const ENROLLED_STATUSES = [
        'enrolled' => 'Enrolled',
        'not_enrolled' => 'Not Enrolled',
        'dropped' => 'Dropped',
        'withdrawn' => 'Withdrawn',
        'on_leave' => 'On Leave',
    ];

    /** How the student stands for this term (new, returning, transfer, or stopped). */
    public const STUDENT_TYPES = [
        'new' => 'New',
        'returning' => 'Returning',
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
            'year_level' => 'integer',
            'gpa' => 'decimal:2',
            'average_grade' => 'decimal:2',
            'attendance_percent' => 'decimal:2',
            'subjects_count' => 'integer',
            'failed_subjects' => 'integer',
            'units_enrolled' => 'decimal:1',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentRecord $record): void {
            $record->student_id = strtoupper(trim((string) $record->student_id));
            $record->section = strtoupper(trim((string) $record->section));
            $record->risk_level = self::assessRisk($record->getAttributes());
        });
    }

    /**
     * GPA uses the 1.00 (highest) to 5.00 (failing) scale.
     * Risk is derived so a name is never required to flag a student.
     */
    public static function assessRisk(array $attributes): string
    {
        $gpa = (float) ($attributes['gpa'] ?? 0);
        $attendance = (float) ($attributes['attendance_percent'] ?? 100);
        $failed = (int) ($attributes['failed_subjects'] ?? 0);
        $enrolled = (string) ($attributes['enrolled_status'] ?? 'enrolled');
        $studentType = (string) ($attributes['student_type'] ?? 'new');
        $tuition = (string) ($attributes['tuition_status'] ?? 'paid');

        if (
            in_array($enrolled, ['dropped', 'withdrawn'], true)
            || $studentType === 'withdraw_stop'
            || $failed >= 3
            || $attendance < 60
            || $gpa > 3
        ) {
            return 'critical';
        }

        if ($failed >= 2 || $attendance < 75 || $gpa >= 2.75 || $tuition === 'unpaid') {
            return 'high';
        }

        if (
            $failed >= 1
            || $attendance < 85
            || $gpa >= 2.25
            || $tuition === 'partial'
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

        return $code === 'IT' ? 'Information Technology' : 'Engineering';
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
            'transferee' => 'bg-violet-100 text-violet-800',
            'withdraw_stop' => 'bg-red-100 text-red-700',
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
