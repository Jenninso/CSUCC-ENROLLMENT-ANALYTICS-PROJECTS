<?php

namespace Tests\Unit;

use App\Services\StudentAttritionService;
use Tests\TestCase;

class StudentAttritionTest extends TestCase
{
    public function test_random_forest_flags_enrolled_students_who_resemble_those_who_left(): void
    {
        $rows = [];
        for ($i = 1; $i <= 80; $i++) {
            $rows[] = $this->student($i, 'dropped', 3.1, 64, 3);
            $rows[] = $this->student(1000 + $i, 'enrolled', 1.6, 94, 0);
        }
        $rows[] = $this->student(9000, 'enrolled', 3.2, 61, 3);

        $result = (new StudentAttritionService)->fromRows($rows);

        $this->assertTrue($result['ok']);
        $this->assertGreaterThan(70, $result['accuracy']);
        $this->assertNotEmpty($result['students']);
        $this->assertSame('STU-9000', $result['students'][0]['student_id']);
        $this->assertStringContainsString('tutoring', $result['students'][0]['suggestion']);
        $this->assertStringContainsString('absences', $result['students'][0]['suggestion']);
        $this->assertStringContainsString('guidance counseling', $result['students'][0]['suggestion']);
    }

    private function student(int $id, string $status, float $gpa, float $attendance, int $failed): object
    {
        return (object) [
            'student_id' => 'STU-'.$id,
            'year_level' => 2,
            'age' => 19,
            'gpa' => $gpa,
            'attendance_percent' => $attendance,
            'failed_subjects' => $failed,
            'subjects_count' => 8,
            'units_enrolled' => 21,
            'gender' => 'female',
            'scholarship_status' => 'none',
            'enrolled_status' => $status,
            'student_type' => $status === 'dropped' ? 'returning' : 'returning',
            'program_code' => 'BSIT',
        ];
    }
}
