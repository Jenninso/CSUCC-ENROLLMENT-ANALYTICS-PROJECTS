<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Semester;
use App\Models\StudentRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatisticalHistorySeeder extends Seeder
{
    /** @var array<string, array{base: int, step: int}> */
    private array $profiles = [
        'BSIT' => ['base' => 520, 'step' => 38],
        'BSCpE' => ['base' => 250, 'step' => 16],
        'BSEE' => ['base' => 180, 'step' => 11],
    ];

    public function run(): void
    {
        $programs = Program::query()
            ->with('department')
            ->whereIn('program_code', array_keys($this->profiles))
            ->get()
            ->keyBy('program_code');

        foreach (array_keys($this->profiles) as $code) {
            if (! isset($programs[$code]) || $programs[$code]->department === null) {
                throw new \RuntimeException("Program {$code} with a department is required before seeding statistical history.");
            }
        }

        // Skip if statistical data already exists (idempotent guard for Render container restarts).
        $programIds = $programs->pluck('id')->all();
        $existingCount = DB::table('student_records')->whereIn('program_id', $programIds)->count();
        if ($existingCount >= 100) {
            $this->command?->info("StatisticalHistorySeeder: {$existingCount} records already exist — skipping re-seed.");

            return;
        }

        $terms = $this->academicTerms();

        DB::table('spes_applications')->whereIn('student_record_id', function ($query) use ($programIds) {
            $query->select('id')->from('student_records')->whereIn('program_id', $programIds);
        })->delete();
        DB::table('alerts')->where('metric', 'student_risk')->whereIn('program_id', $programIds)->delete();
        DB::table('student_records')->whereIn('program_id', $programIds)->delete();
        DB::table('enrollment_records')->whereIn('program_id', $programIds)->delete();

        $now = now();
        $students = [];
        $enrollments = [];
        $studentCount = 0;
        $enrollmentCount = 0;

        foreach ($programs as $code => $program) {
            $profile = $this->profiles[$code];
            foreach ($terms as $index => $term) {
                $wave = 1 + (sin($index / 2) * 0.015);
                $firstTotal = (int) round(($profile['base'] + ($profile['step'] * $index)) * $wave);
                $secondTotal = (int) round($firstTotal * 0.93);
                $studentCount += $this->writeTerm($program, $term['year'], $term['first'], $firstTotal, 1, $term['start'], $students, $enrollments, $now);
                $studentCount += $this->writeTerm($program, $term['year'], $term['second'], $secondTotal, 2, $term['start'], $students, $enrollments, $now);
                $enrollmentCount += $this->flush($enrollments, 'enrollment_records');
            }
        }

        $studentCount += 0;
        $this->flush($students, 'student_records');
        $this->flush($enrollments, 'enrollment_records');

        $this->dropYearsBefore(2020);

        $this->command?->info('Statistical history ready: '.number_format(StudentRecord::count()).' student rows, '.number_format(EnrollmentRecord::count()).' enrollment rows.');
        $this->command?->info('Inserted about '.number_format($studentCount).' student-term rows for '.implode(', ', array_keys($this->profiles)).' from 2020-2021 through 2026-2027.');
        $this->call(SprSyntheticSeeder::class);
    }

    /**
     * @return list<array{year: AcademicYear, first: Semester, second: Semester, start: int}>
     */
    private function academicTerms(): array
    {
        $terms = [];
        AcademicYear::query()->update(['status' => 'inactive']);

        for ($index = 0; $index < 7; $index++) {
            $start = 2020 + $index;
            $year = AcademicYear::query()->updateOrCreate(
                ['academic_year' => $start.'-'.($start + 1)],
                [
                    'start_date' => "{$start}-08-01",
                    'end_date' => ($start + 1).'-07-31',
                    'status' => $start === 2026 ? 'active' : 'inactive',
                ]
            );
            $terms[] = [
                'year' => $year,
                'first' => Semester::query()->firstOrCreate([
                    'academic_year_id' => $year->id,
                    'semester_name' => 'First Semester',
                ]),
                'second' => Semester::query()->firstOrCreate([
                    'academic_year_id' => $year->id,
                    'semester_name' => 'Second Semester',
                ]),
                'start' => $start,
            ];
        }

        return $terms;
    }

    private function dropYearsBefore(int $startYear): void
    {
        $years = AcademicYear::query()
            ->where('start_date', '<', $startYear.'-01-01')
            ->get();

        foreach ($years as $year) {
            $semesterIds = Semester::query()->where('academic_year_id', $year->id)->pluck('id');
            $stillUsed = DB::table('student_records')->where('academic_year_id', $year->id)->exists()
                || DB::table('enrollment_records')->where('academic_year_id', $year->id)->exists()
                || ($semesterIds->isNotEmpty() && DB::table('student_records')->whereIn('semester_id', $semesterIds)->exists());
            if ($stillUsed) {
                continue;
            }

            Semester::query()->where('academic_year_id', $year->id)->delete();
            $year->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @param  list<array<string, mixed>>  $enrollments
     */
    private function writeTerm(Program $program, AcademicYear $year, Semester $semester, int $total, int $semesterNumber, int $startYear, array &$students, array &$enrollments, \Illuminate\Support\Carbon $now): int
    {
        $levels = [1 => 0.32, 2 => 0.26, 3 => 0.22, 4 => 0.20];
        $assignedLevels = 0;
        $created = 0;
        $levelIndex = 0;

        foreach ($levels as $level => $weight) {
            $levelIndex++;
            $levelTotal = $levelIndex === count($levels) ? $total - $assignedLevels : (int) round($total * $weight);
            $assignedLevels += $levelTotal;
            if ($levelTotal <= 0) {
                continue;
            }

            $buckets = $level === 4
                ? ['graduating' => 0.70, 'new' => 0.15, 'transferee' => 0.08, 'dropped' => 0.04, 'withdrawn' => 0.03]
                : ['new' => 0.30, 'returning' => 0.55, 'transferee' => 0.08, 'dropped' => 0.04, 'withdrawn' => 0.03];
            $assignedBuckets = 0;
            $bucketIndex = 0;

            foreach ($buckets as $bucket => $share) {
                $bucketIndex++;
                $count = $bucketIndex === count($buckets) ? $levelTotal - $assignedBuckets : (int) round($levelTotal * $share);
                $assignedBuckets += $count;
                if ($count <= 0) {
                    continue;
                }

                $enrollments[] = [
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'college_id' => $program->department->college_id,
                    'department_id' => $program->department_id,
                    'program_id' => $program->id,
                    'year_level' => $level,
                    'enrollment_type' => $bucket,
                    'enrollment_count' => $count,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                [$status, $studentType] = match ($bucket) {
                    'dropped' => ['dropped', 'returning'],
                    'withdrawn' => ['withdrawn', 'withdraw_stop'],
                    'transferee' => ['enrolled', 'transferee'],
                    'new' => ['enrolled', 'new'],
                    default => ['enrolled', 'returning'],
                };

                for ($n = 1; $n <= $count; $n++) {
                    $sequence = $created + $n;
                    $left = in_array($status, ['dropped', 'withdrawn'], true);
                    $watch = ! $left && $sequence % 40 === 0;
                    $gpa = $left
                        ? 2.7 + (($sequence % 4) * 0.2)
                        : ($watch ? 2.85 + (($sequence % 3) * 0.15) : 1.5 + (($sequence % 5) * 0.15));
                    $attendance = $left
                        ? 60 + ($sequence % 15)
                        : ($watch ? 64 + ($sequence % 8) : 88 + ($sequence % 10));
                    $failed = $left ? 2 + ($sequence % 2) : ($watch ? 2 : ($sequence % 17 === 0 ? 1 : 0));
                    $students[] = [
                        'student_id' => sprintf('%s-%02d%d-%05d', $program->program_code, $startYear % 100, $semesterNumber, $sequence),
                        'gender' => $sequence % 2 === 0 ? 'male' : 'female',
                        'age' => 17 + $level + ($sequence % 3),
                        'program_id' => $program->id,
                        'year_level' => $level,
                        'section' => $sequence % 3 === 0 ? 'B' : 'A',
                        'academic_year_id' => $year->id,
                        'semester_id' => $semester->id,
                        'gpa' => $gpa,
                        'average_grade' => 85 + ($sequence % 8),
                        'attendance_percent' => $attendance,
                        'subjects_count' => 8,
                        'failed_subjects' => $failed,
                        'units_enrolled' => 21.0,
                        'enrolled_status' => $status,
                        'student_type' => $studentType,
                        'scholarship_status' => $sequence % 9 === 0 ? 'academic' : 'none',
                        'tuition_status' => 'paid',
                        'risk_level' => StudentRecord::assessRisk([
                            'gpa' => $gpa,
                            'attendance_percent' => $attendance,
                            'failed_subjects' => $failed,
                            'enrolled_status' => $status,
                            'student_type' => $studentType,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($students) >= 1000) {
                        $this->flush($students, 'student_records');
                    }
                }

                $created += $count;
                if (count($enrollments) >= 1000) {
                    $this->flush($enrollments, 'enrollment_records');
                }
            }
        }

        return $created;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function flush(array &$rows, string $table): int
    {
        if ($rows === []) {
            return 0;
        }

        $count = count($rows);
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
        $rows = [];

        return $count;
    }
}
