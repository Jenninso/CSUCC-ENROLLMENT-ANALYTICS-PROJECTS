<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role;
use App\Models\SecretQuestion;
use App\Models\Semester;
use App\Models\SpesApplication;
use App\Models\StudentRecord;
use App\Models\StudentStatusReason;
use App\Models\User;
use App\Models\UserSecretAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_account_is_separate_and_can_submit_spes_and_a_reason(): void
    {
        [$student, $other] = $this->students();

        $this->actingAs($student)
            ->get(route('students.index'))
            ->assertRedirect(route('student.home'));

        $this->actingAs($student)
            ->get(route('student.home'))
            ->assertOk()
            ->assertSee('SPES')
            ->assertSee('Shift, transfer, or withdrawal');

        $this->actingAs($student)
            ->post(route('student.spes.store'), $this->spesPayload())
            ->assertRedirect();

        $application = SpesApplication::query()->first();
        $this->assertSame($student->id, $application->user_id);
        $this->assertSame('2025-01001', $application->student_id);
        $this->assertSame('submitted', $application->status);

        $application->update(['status' => 'approved', 'student_can_edit' => false]);
        $this->actingAs($student)
            ->get(route('student.spes.edit', $application))
            ->assertRedirect(route('student.spes.show', $application));

        $this->actingAs($other)
            ->get(route('student.spes.show', $application))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.reasons.store'), [
                'action_type' => 'shift_program',
                'from_program' => 'BS Information Technology',
                'to_program' => 'BS Computer Engineering',
                'reason' => 'I shifted because the mathematics load in my first program no longer matched the work I can take while studying.',
                'effective_date' => '2026-11-01',
            ])
            ->assertRedirect(route('student.reasons.index'));

        $this->actingAs($student)
            ->post(route('student.reasons.store'), [
                'action_type' => 'withdraw_subject',
                'subject_name' => 'Calculus 1',
                'reason' => 'I withdrew Calculus 1 after missing the first three weeks because of a family emergency.',
            ])
            ->assertRedirect(route('student.reasons.index'));

        $this->assertSame(2, StudentStatusReason::query()->where('user_id', $student->id)->count());

        $staff = User::factory()->create([
            'role_id' => Role::query()->where('role_name', 'Super Administrator')->value('id'),
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('spes.student-access', $application), ['allow' => '1'])
            ->assertRedirect();

        $this->assertTrue($application->fresh()->student_can_edit);

        $this->actingAs($student)
            ->get(route('student.spes.edit', $application))
            ->assertOk()
            ->assertSee('Double check your Form');
    }

    public function test_student_registration_requires_an_existing_student_id(): void
    {
        Mail::fake();
        $this->scope();
        foreach (['What is your pet?', 'What is your city?', 'What is your school?'] as $question) {
            SecretQuestion::create(['question' => $question, 'is_active' => true]);
        }
        $questions = SecretQuestion::query()->orderBy('id')->pluck('id');

        $this->post(route('student.register'), [
            'student_id' => '2099-00001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.student@example.com',
            'password' => 'Password1a',
            'password_confirmation' => 'Password1a',
            'question_1' => $questions[0],
            'question_2' => $questions[1],
            'question_3' => $questions[2],
            'answer_1' => 'cat',
            'answer_2' => 'city',
            'answer_3' => 'school',
        ])->assertSessionHasErrors('student_id')
            ->assertDontSee('value="cat"', false)
            ->assertDontSee('>cat<', false);

        StudentRecord::create([
            'student_id' => '2025-01001',
            'gender' => 'female',
            'age' => 19,
            'program_id' => Program::query()->value('id'),
            'year_level' => 2,
            'section' => 'A',
            'academic_year_id' => AcademicYear::query()->value('id'),
            'semester_id' => Semester::query()->value('id'),
            'gpa' => 1.75,
            'average_grade' => 90,
            'attendance_percent' => 95,
            'subjects_count' => 8,
            'failed_subjects' => 0,
            'units_enrolled' => 23,
            'enrolled_status' => 'enrolled',
            'student_type' => 'returning',
            'scholarship_status' => 'none',
            'tuition_status' => 'paid',
            'risk_level' => 'low',
        ]);

        $this->post(route('student.register'), [
            'student_id' => '2025-01001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.student@example.com',
            'password' => 'Password1a',
            'password_confirmation' => 'Password1a',
            'question_1' => $questions[0],
            'question_2' => $questions[1],
            'question_3' => $questions[2],
            'answer_1' => 'cat',
            'answer_2' => 'city',
            'answer_3' => 'school',
        ])->assertRedirect(route('student.register.otp'));

        $this->post(route('student.register.otp'), ['otp' => session('otp_hint')])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'employee_id' => '2025-01001',
            'username' => '2025-01001',
            'role_id' => Role::query()->where('role_name', 'Student')->value('id'),
        ]);

        $stored = UserSecretAnswer::query()->first();
        $this->assertNotNull($stored);
        $this->assertNotSame('cat', $stored->answer_hash);
        $this->assertTrue(password_verify('cat', $stored->answer_hash));
    }

    private function students(): array
    {
        $this->scope();
        $role = Role::query()->where('role_name', 'Student')->first();
        $make = fn (string $id, string $email) => User::factory()->create([
            'employee_id' => $id,
            'username' => strtolower($id),
            'email' => $email,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        return [$make('2025-01001', 'one@student.test'), $make('2025-01002', 'two@student.test')];
    }

    private function scope(): void
    {
        Role::firstOrCreate(['role_name' => 'Super Administrator'], ['description' => 'Super Admin']);
        Role::firstOrCreate(['role_name' => 'Student'], ['description' => 'Student portal']);
        $college = College::create([
            'college_name' => 'College of Engineering and Information Technology',
            'college_code' => 'CEIT',
        ]);
        $department = Department::create([
            'department_name' => 'Information Technology',
            'department_code' => 'IT',
            'college_id' => $college->id,
        ]);
        Program::create([
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
        Semester::create([
            'semester_name' => 'First Semester',
            'academic_year_id' => $year->id,
        ]);
    }

    private function spesPayload(): array
    {
        return [
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'sex' => 'female',
            'civil_status' => 'single',
            'birth_date' => '2007-03-15',
            'place_of_birth' => 'Cabadbaran City',
            'address' => 'P-1',
            'barangay' => 'Comagascas',
            'city_municipality' => 'Cabadbaran City',
            'province' => 'Agusan del Norte',
            'contact_number' => '09171234567',
            'parents_annual_income' => 84000,
            'number_of_siblings' => 2,
            'education_level' => 'tertiary',
            'school_name' => 'Caraga State University — Cabadbaran Campus',
            'course' => 'BS Information Technology',
            'year_level' => '2nd Year',
            'enrollment_status' => 'enrolled',
            'application_year' => 2026,
            'work_period' => 'summer',
            'preferred_days' => 20,
            'beneficiary_type' => 'student',
            'previous_spes' => '0',
            'has_birth_certificate' => '0',
            'has_enrollment_cert' => '0',
            'has_grades' => '0',
            'has_indigency' => '0',
            'has_photo' => '0',
            'application_date' => '2026-11-12',
            'applicant_signature' => 'Maria Santos',
            'certified' => '1',
            'status' => 'approved',
        ];
    }
}
