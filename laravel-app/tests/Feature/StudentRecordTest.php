<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_is_critical_when_gpa_attendance_or_failures_are_severe(): void
    {
        $this->assertSame('critical', StudentRecord::assessRisk([
            'gpa' => 3.50,
            'attendance_percent' => 90,
            'failed_subjects' => 0,
            'enrolled_status' => 'enrolled',
            'tuition_status' => 'paid',
        ]));

        $this->assertSame('low', StudentRecord::assessRisk([
            'gpa' => 1.50,
            'attendance_percent' => 96,
            'failed_subjects' => 0,
            'enrolled_status' => 'enrolled',
            'tuition_status' => 'paid',
        ]));
    }

    public function test_student_record_is_listed_by_id_without_a_name(): void
    {
        $this->seedScope();
        $user = User::factory()->create([
            'role_id' => Role::query()->where('role_name', 'Super Administrator')->value('id'),
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $program = Program::query()->with('department')->first();
        $year = AcademicYear::query()->first();
        $semester = Semester::query()->first();

        $this->actingAs($user)->post(route('students.store'), [
            'student_id' => '2025-01001',
            'gender' => 'female',
            'age' => 19,
            'college_id' => $program->department->college_id,
            'program_id' => $program->id,
            'year_level' => 1,
            'section' => 'a',
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
        ])->assertRedirect(route('students.index'));

        $record = StudentRecord::query()->first();
        $this->assertNotNull($record);
        $this->assertSame('2025-01001', $record->student_id);
        $this->assertSame('A', $record->section);
        $this->assertSame('low', $record->risk_level);
        $this->assertSame('returning', $record->student_type);
        $this->assertArrayNotHasKey('name', $record->getAttributes());

        $this->actingAs($user)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('2025-01001')
            ->assertSee('Student ID')
            ->assertSee('Returning')
            ->assertSee('CEIT')
            ->assertSee('BSIT')
            ->assertSee('Information Technology')
            ->assertDontSee('Student Name');

        $this->actingAs($user)
            ->get(route('students.show', $record))
            ->assertOk()
            ->assertSee('Student Type')
            ->assertSee('Returning');
    }

    public function test_student_csv_import_preview_and_commit(): void
    {
        $this->seedScope();
        $user = User::factory()->create([
            'role_id' => Role::query()->where('role_name', 'Super Administrator')->value('id'),
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $csv = implode("\n", [
            'student_id,gender,age,college,course,year_level,section,academic_year,semester,gpa,average_grade,attendance_percent,number_of_subjects,failed_subjects,units_enrolled,enrolled_status,student_type,scholarship_status,tuition_status',
            '2025-02001,Female,19,CEIT,BSIT,1,A,2025-2026,First Semester,1.75,90,95,8,0,24,Enrolled,Transferee,None,Paid',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $this->actingAs($user)
            ->post(route('students.import.preview'), ['file' => $file])
            ->assertRedirect(route('students.import'));

        $preview = session('student_import_preview');
        $this->assertNotNull($preview);
        $this->assertSame(1, $preview['counts']['valid']);
        $this->assertSame(0, $preview['counts']['invalid']);

        $this->actingAs($user)
            ->post(route('students.import.confirm'))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('student_records', [
            'student_id' => '2025-02001',
            'year_level' => 1,
            'section' => 'A',
            'student_type' => 'transferee',
            'risk_level' => 'low',
        ]);
    }

    public function test_withdraw_stop_student_type_is_critical_risk(): void
    {
        $this->assertSame('critical', StudentRecord::assessRisk([
            'gpa' => 1.50,
            'attendance_percent' => 96,
            'failed_subjects' => 0,
            'enrolled_status' => 'enrolled',
            'student_type' => 'withdraw_stop',
            'tuition_status' => 'paid',
        ]));
    }

    private function seedScope(): void
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
};
