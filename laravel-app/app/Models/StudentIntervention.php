<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'student_record_id',
    'intervention_type',
    'notes',
    'provided_on',
    'outcome',
    'gpa_at_record',
    'attendance_at_record',
    'recorded_by',
])]
class StudentIntervention extends Model
{
    public const TYPES = [
        'tutoring' => 'Academic tutoring and remedial classes',
        'attendance_followup' => 'Attendance monitoring and follow-up',
        'counseling' => 'Guidance counseling and student consultation',
        'financial_referral' => 'Financial assistance or scholarship referral',
        'guardian_coordination' => 'Coordination with parent or guardian',
        'working_student_support' => 'Flexible support for a working student',
        'school_program' => 'Referral to a school support program',
    ];

    public const OUTCOMES = [
        'pending' => 'Pending review',
        'improved' => 'Improved',
        'unchanged' => 'Unchanged',
        'worsened' => 'Worsened',
    ];

    protected function casts(): array
    {
        return [
            'provided_on' => 'date',
            'gpa_at_record' => 'decimal:2',
            'attendance_at_record' => 'decimal:2',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class, 'student_record_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->intervention_type] ?? ucfirst((string) $this->intervention_type);
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ucfirst((string) $this->outcome);
    }
}
