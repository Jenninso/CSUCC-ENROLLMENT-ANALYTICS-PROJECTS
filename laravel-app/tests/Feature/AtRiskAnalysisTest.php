<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SpesApplication;
use App\Models\StudentIntervention;
use App\Models\StudentRecord;
use App\Models\User;
use App\Services\StudentRiskAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtRiskAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_does_not_raise_an_academically_sound_student(): void
    {
        [$program, $year, $semester] = $this->scope();
        $record = $this->record($program, $year, $semester, [
            'student_id' => '2025-01001',
            'gpa' => 1.50,
            'attendance_percent' => 96,
            'failed_subjects' => 0,
            'tuition_status' => 'unpaid',
        ]);
        $this->spr($record, [
            'estimated_income' => 'Below 10,000',
            'four_ps' => 'yes',
            'relationship' => 'Grandmother',
            'living_with_parents' => 'no',
            'support' => ['parents', 'employed'],
        ], 96000);

        $analysis = app(StudentRiskAnalysis::class)->forStudent($record);

        $this->assertSame('low', $analysis['level']);
        $this->assertContains('low_income', $analysis['factor_keys']);
        $this->assertContains('grandparent', $analysis['factor_keys']);
        $this->assertContains('working_student', $analysis['factor_keys']);
        $this->assertStringContainsString('were not used to raise this classification', $analysis['narrative']);
    }

    public function test_a_sharp_grade_decline_raises_the_academic_level_by_one_step(): void
    {
        [$program, $year, $first] = $this->scope();
        $second = Semester::create([
            'semester_name' => 'Second Semester',
            'academic_year_id' => $year->id,
            'semester_order' => 2,
        ]);

        $this->record($program, $year, $first, [
            'student_id' => '2025-01008',
            'gpa' => 1.50,
            'attendance_percent' => 95,
            'failed_subjects' => 0,
        ]);
        $latest = $this->record($program, $year, $second, [
            'student_id' => '2025-01008',
            'gpa' => 2.10,
            'attendance_percent' => 94,
            'failed_subjects' => 0,
        ]);

        $analysis = app(StudentRiskAnalysis::class)->forStudent($latest);

        $this->assertSame('low', $analysis['academic_level']);
        $this->assertSame('moderate', $analysis['level']);
        $this->assertTrue($analysis['declining_grades']);
        $this->assertStringContainsString('Academic tutoring', $analysis['recommendations'][0]['title']);
    }

    public function test_staff_can_open_the_report_and_record_an_intervention(): void
    {
        [$program, $year, $semester] = $this->scope();
        $record = $this->record($program, $year, $semester, [
            'student_id' => '2025-03001',
            'gpa' => 3.25,
            'attendance_percent' => 70,
            'failed_subjects' => 2,
        ]);
        $staff = User::factory()->create([
            'role_id' => Role::query()->where('role_name', 'Super Administrator')->value('id'),
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $this->actingAs($staff)->withSession(['_token' => 'test-token']);

        $this->get(route('at-risk.index'))
            ->assertOk()
            ->assertSee('Student at-risk analysis')
            ->assertSee('2025-03001')
            ->assertSee('do not raise');

        $this->actingAs($staff)
            ->get(route('at-risk.show', $record))
            ->assertOk()
            ->assertSee('System analysis')
            ->assertSee('Critical');

        $this->actingAs($staff)
            ->post(route('at-risk.interventions.store', $record), [
                '_token' => 'test-token',
                'intervention_type' => 'counseling',
                'provided_on' => '2026-11-12',
                'notes' => 'Met with the student after midterms.',
            ])
            ->assertRedirect(route('at-risk.show', $record));

        $this->assertDatabaseHas('student_interventions', [
            'student_id' => '2025-03001',
            'intervention_type' => 'counseling',
            'outcome' => 'pending',
        ]);

        $intervention = StudentIntervention::query()->first();
        $this->actingAs($staff)
            ->put(route('at-risk.interventions.update', $intervention), [
                '_token' => 'test-token',
                'outcome' => 'improved',
            ])
            ->assertRedirect();
        $this->assertSame('improved', $intervention->fresh()->outcome);

        $this->actingAs($staff)
            ->post(route('at-risk.warnings'), ['_token' => 'test-token'])
            ->assertRedirect(route('at-risk.index'));
        $this->assertDatabaseHas('alerts', [
            'title' => 'At-risk warning: 2025-03001',
            'metric' => 'student_risk',
        ]);
    }

    public function test_unpaid_tuition_alone_is_not_an_academic_risk(): void
    {
        $this->assertSame('low', StudentRecord::assessRisk([
            'gpa' => 1.50,
            'attendance_percent' => 96,
            'failed_subjects' => 0,
            'enrolled_status' => 'enrolled',
            'tuition_status' => 'unpaid',
        ]));
    }

    /**
     * @return array{0: Program, 1: AcademicYear, 2: Semester}
     */
    private function scope(): array
    {
        Role::firstOrCreate(['role_name' => 'Super Administrator'], ['description' => 'Super Admin']);
        $college = College::create([
            'college_name' => 'College of Engineering and Information Technology',
            'college_code' => 'CEIT',
        ]);
        $department = Department::create([
            'department_name' => 'Information Technology',
            'department_code' => 'IT',
            'college_id' => $college->id,
        ]);
        $program = Program::create([
            'program_name' => 'BS Information Technology',
            'program_code' => 'BSIT',
            'department_id' => $department->id,
            'degree_level' => 'Bachelor',
            'status' => 'active',
        ]);
        $year = AcademicYear::create([
            'academic_year' => '2025-2026',
            'start_date' => '2025-08-01',
            'end_date' => '2026-07-31',
            'status' => 'active',
        ]);
        $semester = Semester::create([
            'semester_name' => 'First Semester',
            'academic_year_id' => $year->id,
            'semester_order' => 1,
        ]);

        return [$program, $year, $semester];
    }

    private function record(Program $program, AcademicYear $year, Semester $semester, array $overrides): StudentRecord
    {
        return StudentRecord::create(array_merge([
            'student_id' => '2025-01001',
            'gender' => 'female',
            'age' => 19,
            'program_id' => $program->id,
            'year_level' => 1,
            'section' => 'A',
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'gpa' => 1.75,
            'average_grade' => 90,
            'attendance_percent' => 95,
            'subjects_count' => 8,
            'failed_subjects' => 0,
            'units_enrolled' => 24,
            'enrolled_status' => 'enrolled',
            'student_type' => 'returning',
            'scholarship_status' => 'none',
            'tuition_status' => 'paid',
        ], $overrides));
    }

    private function spr(StudentRecord $record, array $sprData, float $annual): SpesApplication
    {
        return SpesApplication::create([
            'reference_no' => 'SPR-TEST-'.$record->student_id,
            'student_record_id' => $record->id,
            'student_id' => $record->student_id,
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'birth_date' => '2007-03-15',
            'age' => 19,
            'place_of_birth' => 'Cabadbaran City',
            'address' => 'Purok 1',
            'barangay' => 'Comagascas',
            'city_municipality' => 'Cabadbaran City',
            'province' => 'Agusan del Norte',
            'contact_number' => '09171234567',
            'father_name' => 'Pedro Santos',
            'father_occupation' => 'Farmer',
            'mother_name' => 'Ana Reyes',
            'mother_occupation' => 'Vendor',
            'parents_annual_income' => $annual,
            'education_level' => 'undergraduate',
            'school_name' => 'Cabadbaran City NHS',
            'course' => 'BS Information Technology',
            'year_level' => '1st Year',
            'enrollment_status' => 'old_student',
            'application_year' => 2026,
            'work_period' => 'na',
            'preferred_days' => 0,
            'beneficiary_type' => 'student',
            'application_date' => '2026-08-01',
            'applicant_signature' => 'Maria Reyes Santos',
            'status' => 'submitted',
            'spr_data' => array_merge([
                'father_status' => 'living',
                'mother_status' => 'living',
                'support' => ['parents'],
            ], $sprData),
        ]);
    }
}
