<?php

namespace App\Services;

use App\Models\StudentRecord;
use Illuminate\Support\Collection;

class MovementAdviceService
{
    /**
     * Staff advice for shiftees, transferees, and withdrawals.
     * Records do not store a written reason, so the advice uses grade, attendance, and tuition signals.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array{title: string, count: int, reason: string, action: string}>
     */
    public function summarize(array $filters): array
    {
        $records = $this->records($filters);

        return [
            $this->group(
                'Shiftees',
                $records->where('student_type', 'shiftee')->values(),
                'Confirm why each student shifted into this program during the first advising session.',
            ),
            $this->group(
                'Transferred',
                $records->where('student_type', 'transferee')->values(),
                'Check the transferring school record and place the student in the correct year level.',
            ),
            $this->group(
                'Withdrawn',
                $records->filter(fn (StudentRecord $record) => $record->enrolled_status === 'withdrawn' || $record->student_type === 'withdraw_stop')->values(),
                'Hold an exit interview and record whether the student can return next term.',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, StudentRecord>
     */
    private function records(array $filters): Collection
    {
        return StudentRecord::query()
            ->when($filters['academic_year_id'] ?? null, fn ($query, $year) => $query->where('academic_year_id', $year))
            ->when($filters['semester_name'] ?? null, function ($query, $name) {
                $query->whereHas('semester', fn ($semester) => $semester->where('semester_name', $name));
            })
            ->when($filters['program_id'] ?? null, fn ($query, $program) => $query->where('program_id', $program))
            ->when($filters['year_level'] ?? null, fn ($query, $level) => $query->where('year_level', $level))
            ->when($filters['college_id'] ?? null, function ($query, $college) {
                $query->whereHas('program.department', fn ($department) => $department->where('college_id', $college));
            })
            ->get();
    }

    /**
     * @param  Collection<int, StudentRecord>  $records
     * @return array{title: string, count: int, reason: string, action: string}
     */
    private function group(string $title, Collection $records, string $emptyAction): array
    {
        $count = $records->count();
        if ($count === 0) {
            return [
                'title' => $title,
                'count' => 0,
                'reason' => 'No student records in this group for the selected term.',
                'action' => $emptyAction,
            ];
        }

        $academic = $records->filter(fn (StudentRecord $record) => (int) $record->failed_subjects >= 1 || (float) $record->gpa >= 2.5)->count();
        $attendance = $records->filter(fn (StudentRecord $record) => (float) $record->attendance_percent < 85)->count();
        $tuition = $records->filter(fn (StudentRecord $record) => in_array($record->tuition_status, ['unpaid', 'partial'], true))->count();

        $signals = [
            'academic' => $academic,
            'attendance' => $attendance,
            'tuition' => $tuition,
        ];
        arsort($signals);
        $top = array_key_first($signals);
        $topCount = $signals[$top];

        if ($topCount === 0) {
            return [
                'title' => $title,
                'count' => $count,
                'reason' => 'Grades, attendance, and tuition do not explain this group. The stated reason still has to be written down at advising.',
                'action' => $emptyAction,
            ];
        }

        [$reason, $action] = match ($top) {
            'attendance' => [
                $topCount.' of '.$count.' have attendance below 85%. Attendance is the strongest recorded signal.',
                'Meet these students, set a catch-up schedule, and review the case before the next grading period.',
            ],
            'tuition' => [
                $topCount.' of '.$count.' have unpaid or partial tuition. Cost is the strongest recorded signal.',
                'Refer them to the scholarship and student affairs office before the next enrollment.',
            ],
            default => [
                $topCount.' of '.$count.' have a failing subject or a GPA of 2.50 or higher. Academic difficulty is the strongest recorded signal.',
                'Assign an adviser and a tutorial or bridging plan before the next grading period.',
            ],
        };

        return [
            'title' => $title,
            'count' => $count,
            'reason' => $reason,
            'action' => $action,
        ];
    }
}
