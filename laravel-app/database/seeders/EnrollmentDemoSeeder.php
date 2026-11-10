<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::setValue('institution_name', 'Caraga State University Cabadbaran Campus', 'general');
        SystemSetting::setValue('decline_threshold_pct', -8, 'alerts');
        SystemSetting::setValue('growth_threshold_pct', 12, 'alerts');
        SystemSetting::setValue('retention_target', 85, 'alerts');
        SystemSetting::setValue('institution_capacity', 800, 'alerts');

        $roles = Role::query()->pluck('id', 'role_name');
        $userRoleId = $roles['User'] ?? $roles->last();
        $questions = \App\Models\SecretQuestion::query()->orderBy('id')->limit(3)->pluck('id')->all();
        $hash = app(\App\Services\AccountService::class)->hashAnswer('sample');
        $qIds = $questions;
        foreach ([
            ['Juan', 'Ramos', 'Mendoza', 'registrar@enrollment.com', 'EMP-1001'],
            ['Liza', 'Torres', 'Santos', 'decision@enrollment.com', 'EMP-1002'],
            ['Carlo', 'Diaz', 'Villanueva', 'analyst@enrollment.com', 'EMP-1003'],
        ] as [$first, $middle, $last, $email, $emp]) {
            if (! isset($roles['User'])) {
                continue;
            }
            $demo = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'employee_id' => $emp,
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'name' => User::composeName($first, $middle, $last),
                    'password' => 'password',
                    'role_id' => $userRoleId,
                    'status' => 'active',
                    'must_change_password' => false,
                    'credentials_completed_at' => now(),
                ]
            );
            if ($demo->secretAnswers()->count() === 0 && count($qIds) === 3) {
                foreach ($qIds as $questionId) {
                    $demo->secretAnswers()->create([
                        'secret_question_id' => $questionId,
                        'answer_hash' => $hash,
                    ]);
                }
            }
        }

        // System scope: CEIT college and BSIT program only.
        $ceit = College::create([
            'college_name' => 'College of Engineering and Information Technology',
            'college_code' => 'CEIT',
        ]);

        $it = Department::create([
            'department_name' => 'Information Technology',
            'department_code' => 'IT',
            'college_id' => $ceit->id,
        ]);

        $programs = [
            Program::create([
                'program_name' => 'BS Information Technology',
                'program_code' => 'BSIT',
                'department_id' => $it->id,
                'degree_level' => 'Bachelor',
                'status' => 'active',
            ]),
        ];

        $bases = [
            'BSIT' => [420, 460, 510, 560, 640, 700, 760],
        ];

        $years = [];
        for ($i = 0; $i < 7; $i++) {
            $start = 2021 + $i;
            $year = AcademicYear::create([
                'academic_year' => $start.'-'.($start + 1),
                'start_date' => "{$start}-08-01",
                'end_date' => ($start + 1).'-07-31',
                'status' => $i === 4 ? 'active' : 'inactive',
            ]);
            $first = Semester::create(['semester_name' => 'First Semester', 'academic_year_id' => $year->id]);
            $second = Semester::create(['semester_name' => 'Second Semester', 'academic_year_id' => $year->id]);
            $years[] = [$year, $first, $second, $i];
        }

        foreach ($programs as $program) {
            $series = $bases[$program->program_code];
            foreach ($years as [$year, $first, $second, $idx]) {
                $total = $series[$idx];
                $this->writeProgramTerm($program, $year, $first, $total, $idx);
                $this->writeProgramTerm($program, $year, $second, (int) round($total * 0.92), $idx);
            }
        }
    }

    private function writeProgramTerm(Program $program, AcademicYear $year, Semester $semester, int $total, int $yearIndex): void
    {
        $collegeId = $program->department->college_id;
        $departmentId = $program->department_id;
        $newShare = 0.28 + ($yearIndex * 0.01);
        $parts = [
            'new' => (int) round($total * $newShare),
            'returning' => (int) round($total * 0.42),
            'continuing' => (int) round($total * 0.18),
            'transferee' => (int) round($total * 0.04),
            'graduating' => (int) round($total * 0.06),
            'withdrawn' => (int) round($total * 0.015),
            'dropped' => 0,
        ];
        $parts['dropped'] = max($total - array_sum($parts), 0);

        $levelWeights = [1 => 0.32, 2 => 0.26, 3 => 0.22, 4 => 0.20];
        foreach ($parts as $type => $typeTotal) {
            $assigned = 0;
            foreach ($levelWeights as $level => $weight) {
                $count = $level === 4 ? ($typeTotal - $assigned) : (int) round($typeTotal * $weight);
                $assigned += $count;
                if ($count <= 0) {
                    continue;
                }
                EnrollmentRecord::create([
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'college_id' => $collegeId,
                    'department_id' => $departmentId,
                    'program_id' => $program->id,
                    'year_level' => $level,
                    'enrollment_type' => $type,
                    'enrollment_count' => $count,
                ]);
            }
        }
    }
}
