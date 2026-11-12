<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_year_deletes_with_its_semesters_and_enrollment_counts(): void
    {
        [$user, $program, $active] = $this->scope();
        $year = AcademicYear::create([
            'academic_year' => '2027-2028',
            'start_date' => '2027-08-01',
            'end_date' => '2028-07-31',
            'status' => 'inactive',
        ]);
        $semester = Semester::create([
            'semester_name' => 'First Semester',
            'academic_year_id' => $year->id,
        ]);
        EnrollmentRecord::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'college_id' => $program->department->college_id,
            'department_id' => $program->department_id,
            'program_id' => $program->id,
            'year_level' => 1,
            'enrollment_type' => 'new',
            'enrollment_count' => 16,
        ]);

        $this->actingAs($user)
            ->delete(route('data-management.academic-years.destroy', $year))
            ->assertRedirect(route('data-management.academic-years.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);
        $this->assertDatabaseMissing('semesters', ['id' => $semester->id]);
        $this->assertDatabaseMissing('enrollment_records', ['academic_year_id' => $year->id]);
        $this->assertDatabaseHas('academic_years', ['id' => $active->id]);
    }

    public function test_inactive_year_with_student_records_cannot_be_deleted(): void
    {
        [$user, $program] = $this->scope();
        $year = AcademicYear::create([
            'academic_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'status' => 'inactive',
        ]);
        $semester = Semester::create([
            'semester_name' => 'First Semester',
            'academic_year_id' => $year->id,
        ]);
        StudentRecord::create([
            'student_id' => '2026-01001',
            'gender' => 'female',
            'age' => 19,
            'program_id' => $program->id,
            'year_level' => 1,
            'section' => 'A',
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'gpa' => 1.75,
            'average_grade' => 88,
            'attendance_percent' => 95,
            'subjects_count' => 8,
            'failed_subjects' => 0,
            'units_enrolled' => 23,
            'enrolled_status' => 'enrolled',
            'student_type' => 'new',
            'scholarship_status' => 'none',
            'tuition_status' => 'paid',
            'risk_level' => 'low',
        ]);

        $this->actingAs($user)
            ->from(route('data-management.academic-years.index'))
            ->delete(route('data-management.academic-years.destroy', $year))
            ->assertRedirect(route('data-management.academic-years.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
        $this->assertDatabaseHas('student_records', ['student_id' => '2026-01001']);
    }

    public function test_active_year_with_enrollment_counts_cannot_be_deleted(): void
    {
        [$user, $program, $year, $semester] = $this->scope();
        EnrollmentRecord::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'college_id' => $program->department->college_id,
            'department_id' => $program->department_id,
            'program_id' => $program->id,
            'year_level' => 1,
            'enrollment_type' => 'new',
            'enrollment_count' => 16,
        ]);

        $this->actingAs($user)
            ->from(route('data-management.academic-years.index'))
            ->delete(route('data-management.academic-years.destroy', $year))
            ->assertRedirect(route('data-management.academic-years.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    private function scope(): array
    {
        Role::create(['role_name' => 'Super Administrator', 'description' => 'Super Admin']);
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
        ]);
        $user = User::factory()->create([
            'role_id' => Role::query()->where('role_name', 'Super Administrator')->value('id'),
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        return [$user, $program->load('department'), $year, $semester];
    }
}
