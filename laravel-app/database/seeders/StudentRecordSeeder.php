<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Semester;
use App\Models\StudentRecord;
use Illuminate\Database\Seeder;

class StudentRecordSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::query()->where('status', 'active')->orderByDesc('id')->first()
            ?? AcademicYear::query()->orderByDesc('id')->first();

        if (! $year) {
            return;
        }

        $semesters = Semester::query()
            ->where('academic_year_id', $year->id)
            ->orderBy('id')
            ->get();

        if ($semesters->isEmpty()) {
            return;
        }

        $programs = Program::query()
            ->whereIn('program_code', array_keys(config('scope.programs')))
            ->get()
            ->keyBy('program_code');

        foreach ($this->cohorts() as $code => $cohort) {
            $program = $programs->get($code);
            if (! $program) {
                continue;
            }

            $this->seedCohort($program, $year, $semesters, $cohort);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Semester>  $semesters
     * @param  array{prefix: string, per_section: int, sections: array<int, array<int, string>>}  $cohort
     */
    private function seedCohort(Program $program, AcademicYear $year, $semesters, array $cohort): void
    {
        $startYear = (int) substr((string) $year->academic_year, 0, 4);

        foreach ($cohort['sections'] as $level => $sections) {
            foreach ($sections as $sectionIndex => $section) {
                for ($n = 1; $n <= $cohort['per_section']; $n++) {
                    $entryYear = $startYear - ($level - 1);
                    $serial = ($sectionIndex * $cohort['per_section']) + $n;
                    $studentId = $cohort['prefix'].sprintf('%d-%02d%03d', $entryYear, $level, $serial);
                    $template = $this->templates()[($n + $level + $sectionIndex) % 8];
                    $gender = ($n + $sectionIndex) % 2 === 0 ? 'female' : 'male';
                    $age = 17 + $level + (($n + $sectionIndex) % 3);

                    foreach ($semesters as $semesterIndex => $semester) {
                        $row = $template;
                        $studentType = $level === 1 && $semesterIndex === 0 ? 'new' : 'returning';
                        if ($n === 3) {
                            $studentType = 'transferee';
                        }
                        if ($semesterIndex > 0) {
                            $row['attendance_percent'] = max(50, $row['attendance_percent'] - 3);
                        }
                        if ($program->program_code === 'BSIT' && $level === 1 && $section === 'B' && $n === 8) {
                            $row['enrolled_status'] = 'withdrawn';
                            $row['attendance_percent'] = 48;
                            $row['failed_subjects'] = 3;
                            $row['tuition_status'] = 'unpaid';
                            $studentType = 'withdraw_stop';
                        }

                        StudentRecord::query()->updateOrCreate(
                            [
                                'student_id' => $studentId,
                                'academic_year_id' => $year->id,
                                'semester_id' => $semester->id,
                            ],
                            [
                                'gender' => $gender,
                                'age' => $age,
                                'program_id' => $program->id,
                                'year_level' => $level,
                                'section' => $section,
                                'gpa' => $row['gpa'],
                                'average_grade' => $this->averageGrade($row['gpa']),
                                'attendance_percent' => $row['attendance_percent'],
                                'subjects_count' => $row['subjects_count'],
                                'failed_subjects' => $row['failed_subjects'],
                                'units_enrolled' => $row['units_enrolled'],
                                'enrolled_status' => $row['enrolled_status'],
                                'student_type' => $studentType,
                                'scholarship_status' => $row['scholarship_status'],
                                'tuition_status' => $row['tuition_status'],
                                'risk_level' => 'low',
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array{prefix: string, per_section: int, sections: array<int, array<int, string>>}>
     */
    private function cohorts(): array
    {
        $engineeringSections = [
            1 => ['A'],
            2 => ['A'],
            3 => ['A'],
            4 => ['A'],
        ];

        return [
            'BSIT' => [
                'prefix' => '',
                'per_section' => 8,
                'sections' => [
                    1 => ['A', 'B'],
                    2 => ['A', 'B'],
                    3 => ['A', 'B'],
                    4 => ['A'],
                ],
            ],
            'BSCpE' => [
                'prefix' => 'CPE-',
                'per_section' => 4,
                'sections' => $engineeringSections,
            ],
            'BSEE' => [
                'prefix' => 'EE-',
                'per_section' => 4,
                'sections' => $engineeringSections,
            ],
        ];
    }

    private function averageGrade(float $gpa): float
    {
        return match (true) {
            $gpa <= 1.00 => 99,
            $gpa <= 1.25 => 96,
            $gpa <= 1.50 => 93,
            $gpa <= 1.75 => 90,
            $gpa <= 2.00 => 87,
            $gpa <= 2.25 => 84,
            $gpa <= 2.50 => 81,
            $gpa <= 2.75 => 78,
            $gpa <= 3.00 => 75,
            default => 65,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            ['gpa' => 1.25, 'attendance_percent' => 98, 'subjects_count' => 8, 'failed_subjects' => 0, 'units_enrolled' => 24, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'academic', 'tuition_status' => 'covered'],
            ['gpa' => 1.50, 'attendance_percent' => 96, 'subjects_count' => 8, 'failed_subjects' => 0, 'units_enrolled' => 23, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'none', 'tuition_status' => 'paid'],
            ['gpa' => 1.75, 'attendance_percent' => 94, 'subjects_count' => 9, 'failed_subjects' => 0, 'units_enrolled' => 24, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'government', 'tuition_status' => 'covered'],
            ['gpa' => 2.00, 'attendance_percent' => 91, 'subjects_count' => 8, 'failed_subjects' => 0, 'units_enrolled' => 21, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'none', 'tuition_status' => 'paid'],
            ['gpa' => 2.25, 'attendance_percent' => 84, 'subjects_count' => 8, 'failed_subjects' => 1, 'units_enrolled' => 21, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'none', 'tuition_status' => 'partial'],
            ['gpa' => 2.50, 'attendance_percent' => 80, 'subjects_count' => 9, 'failed_subjects' => 1, 'units_enrolled' => 18, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'private', 'tuition_status' => 'partial'],
            ['gpa' => 2.75, 'attendance_percent' => 72, 'subjects_count' => 8, 'failed_subjects' => 2, 'units_enrolled' => 18, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'none', 'tuition_status' => 'unpaid'],
            ['gpa' => 3.50, 'attendance_percent' => 55, 'subjects_count' => 8, 'failed_subjects' => 3, 'units_enrolled' => 15, 'enrolled_status' => 'enrolled', 'scholarship_status' => 'none', 'tuition_status' => 'unpaid'],
        ];
    }
};
