<?php

namespace App\Services;

use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;

class EnrollmentProjection
{
    /** @var list<string> */
    private array $types = ['new', 'returning', 'continuing', 'transferee', 'graduating', 'withdrawn', 'dropped'];

    public function syncFromStudent(StudentRecord $student): void
    {
        $this->syncSlice(
            (int) $student->academic_year_id,
            (int) $student->semester_id,
            (int) $student->program_id,
            (int) $student->year_level,
        );
    }

    public function syncAll(): void
    {
        $this->rebuildFromStudents();
    }

    /**
     * Rebuild all enrollment projection rows from student_records in one pass.
     */
    public function rebuildFromStudents(): void
    {
        $programs = Program::query()->with('department')->get()->keyBy('id');
        $slices = [];

        StudentRecord::query()
            ->select(['id', 'academic_year_id', 'semester_id', 'program_id', 'year_level', 'enrolled_status', 'student_type'])
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use (&$slices): void {
                foreach ($rows as $student) {
                    $type = $this->typeFor($student);
                    if ($type === null) {
                        continue;
                    }

                    $key = implode(':', [
                        (int) $student->academic_year_id,
                        (int) $student->semester_id,
                        (int) $student->program_id,
                        (int) $student->year_level,
                    ]);

                    if (! isset($slices[$key])) {
                        $slices[$key] = array_fill_keys($this->types, 0);
                    }
                    $slices[$key][$type]++;
                }
            });

        DB::transaction(function () use ($slices, $programs): void {
            EnrollmentRecord::query()->delete();

            $now = now();
            $payload = [];

            foreach ($slices as $key => $counts) {
                [$academicYearId, $semesterId, $programId, $yearLevel] = array_map('intval', explode(':', $key));
                $program = $programs->get($programId);
                if ($program?->department === null) {
                    continue;
                }

                foreach ($counts as $type => $count) {
                    if ($count < 1) {
                        continue;
                    }

                    $payload[] = [
                        'academic_year_id' => $academicYearId,
                        'semester_id' => $semesterId,
                        'college_id' => $program->department->college_id,
                        'department_id' => $program->department_id,
                        'program_id' => $programId,
                        'year_level' => $yearLevel,
                        'enrollment_type' => $type,
                        'enrollment_count' => $count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($payload) >= 500) {
                        DB::table('enrollment_records')->insert($payload);
                        $payload = [];
                    }
                }
            }

            if ($payload !== []) {
                DB::table('enrollment_records')->insert($payload);
            }
        });
    }

    public function syncSlice(int $academicYearId, int $semesterId, int $programId, int $yearLevel): void
    {
        if ($academicYearId < 1 || $semesterId < 1 || $programId < 1 || $yearLevel < 1) {
            return;
        }

        $program = Program::query()->with('department')->find($programId);
        if ($program?->department === null) {
            return;
        }

        $counts = array_fill_keys($this->types, 0);
        StudentRecord::query()
            ->where('academic_year_id', $academicYearId)
            ->where('semester_id', $semesterId)
            ->where('program_id', $programId)
            ->where('year_level', $yearLevel)
            ->get(['enrolled_status', 'student_type', 'year_level'])
            ->each(function (StudentRecord $student) use (&$counts): void {
                $type = $this->typeFor($student);
                if ($type !== null) {
                    $counts[$type]++;
                }
            });

        foreach ($counts as $type => $count) {
            $existing = EnrollmentRecord::query()
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->where('program_id', $programId)
                ->where('year_level', $yearLevel)
                ->where('enrollment_type', $type)
                ->first();

            if ($count === 0) {
                $existing?->delete();
                continue;
            }

            $values = [
                'college_id' => $program->department->college_id,
                'department_id' => $program->department_id,
                'enrollment_count' => $count,
            ];

            if ($existing) {
                $existing->update($values);
                continue;
            }

            EnrollmentRecord::create($values + [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'program_id' => $programId,
                'year_level' => $yearLevel,
                'enrollment_type' => $type,
            ]);
        }
    }

    public function typeFor(StudentRecord $student): ?string
    {
        if ($student->enrolled_status === 'dropped') {
            return 'dropped';
        }

        if ($student->enrolled_status === 'withdrawn' || $student->student_type === 'withdraw_stop') {
            return 'withdrawn';
        }

        if (in_array($student->enrolled_status, ['not_enrolled', 'on_leave'], true)) {
            return null;
        }

        if ((int) $student->year_level >= 4 && $student->student_type !== 'new' && $student->student_type !== 'transferee') {
            return 'graduating';
        }

        return match ($student->student_type) {
            'new' => 'new',
            'returning', 'returnee', 'returnee_shiftee' => 'returning',
            'transferee', 'shiftee' => 'transferee',
            default => 'continuing',
        };
    }
}
