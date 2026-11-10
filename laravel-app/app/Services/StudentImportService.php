<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Semester;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;

class StudentImportService
{
    /** Canonical required headers (aliases normalized before check). */
    public array $requiredColumns = [
        'student_id',
        'gender',
        'age',
        'course',
        'year_level',
        'section',
        'academic_year',
        'semester',
        'gpa',
        'average_grade',
        'attendance_percent',
        'number_of_subjects',
        'failed_subjects',
        'units_enrolled',
        'enrolled_status',
        'student_type',
        'scholarship_status',
        'tuition_status',
    ];

    /** @var array<string, string> */
    private array $headerAliases = [
        'program_code' => 'course',
        'program' => 'course',
        'subjects_count' => 'number_of_subjects',
        'number_of_subject' => 'number_of_subjects',
        'subjects' => 'number_of_subjects',
        'attendance' => 'attendance_percent',
        'attendance_%' => 'attendance_percent',
        'enrolled' => 'enrolled_status',
        'type' => 'student_type',
        'student_category' => 'student_type',
        'enrollment_category' => 'student_type',
        'scholarship' => 'scholarship_status',
        'tuition' => 'tuition_status',
        'student_risk_level' => 'risk_level',
        'risk' => 'risk_level',
        'risk_level' => 'risk_level',
        'college_code' => 'college',
    ];

    public function __construct(private SpreadsheetFileReader $reader) {}

    public function parse(string $path, ?string $originalName = null): array
    {
        $read = $this->reader->read($path, $originalName);
        if (! $read['ok']) {
            return ['ok' => false, 'errors' => $read['errors'], 'rows' => []];
        }

        $matrix = $read['rows'];
        $headerRow = array_shift($matrix);
        if (! $headerRow) {
            return ['ok' => false, 'errors' => ['The file is empty.'], 'rows' => []];
        }

        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headerRow);
        $missing = array_diff($this->requiredColumns, $header);
        if ($missing) {
            return [
                'ok' => false,
                'errors' => ['Missing required columns: '.implode(', ', $missing)],
                'rows' => [],
                'header' => $header,
            ];
        }

        $rows = [];
        $line = 1;
        foreach ($matrix as $data) {
            $line++;
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }
            $combined = array_combine($header, array_slice($data, 0, count($header)));
            if ($combined === false) {
                continue;
            }
            if (trim(implode('', $combined)) === '') {
                continue;
            }
            $rows[] = ['line' => $line, 'data' => $combined];
        }

        return ['ok' => true, 'rows' => $rows, 'header' => $header, 'errors' => []];
    }

    public function validate(array $parsedRows): array
    {
        $issues = [];
        $valid = [];
        $seen = [];

        $years = AcademicYear::query()->get()->keyBy(fn ($y) => strtolower($y->academic_year));
        $programs = Program::query()->with('department.college')->get()->keyBy(fn ($p) => strtolower($p->program_code));
        $semesters = Semester::query()->get();

        foreach ($parsedRows as $row) {
            $line = $row['line'];
            $d = $row['data'];
            $rowErrors = [];

            $studentId = strtoupper(trim((string) ($d['student_id'] ?? '')));
            $gender = $this->mapEnum((string) ($d['gender'] ?? ''), StudentRecord::GENDERS);
            $age = $d['age'] ?? '';
            $course = strtolower(trim((string) ($d['course'] ?? '')));
            $yearLevel = $d['year_level'] ?? '';
            $section = strtoupper(trim((string) ($d['section'] ?? '')));
            $ayKey = strtolower(trim((string) ($d['academic_year'] ?? '')));
            $semName = trim((string) ($d['semester'] ?? ''));
            $gpa = $d['gpa'] ?? '';
            $avgGrade = $d['average_grade'] ?? '';
            $attendance = $d['attendance_percent'] ?? '';
            $subjects = $d['number_of_subjects'] ?? '';
            $failed = $d['failed_subjects'] ?? '';
            $units = $d['units_enrolled'] ?? '';
            $enrolled = $this->mapEnum((string) ($d['enrolled_status'] ?? ''), StudentRecord::ENROLLED_STATUSES);
            $studentType = $this->mapStudentType((string) ($d['student_type'] ?? ''));
            $scholarship = $this->mapEnum((string) ($d['scholarship_status'] ?? ''), StudentRecord::SCHOLARSHIP_STATUSES);
            $tuition = $this->mapEnum((string) ($d['tuition_status'] ?? ''), StudentRecord::TUITION_STATUSES);

            if ($studentId === '' || ! preg_match('/^[A-Z0-9\-]+$/', $studentId)) {
                $rowErrors[] = 'Student ID is required and may contain only letters, numbers, and dashes.';
            }

            if ($gender === null) {
                $rowErrors[] = "Invalid gender '{$d['gender']}'. Use Male/Female or male/female.";
            }

            if (! is_numeric($age) || (int) $age < 15 || (int) $age > 80) {
                $rowErrors[] = 'Age must be an integer from 15 to 80.';
            }

            if ($course === '') {
                $rowErrors[] = 'Missing course / program code.';
            } elseif (! isset($programs[$course])) {
                $rowErrors[] = "Invalid course code '{$d['course']}'.";
            }

            if (! is_numeric($yearLevel) || (int) $yearLevel < 1 || (int) $yearLevel > 4) {
                $rowErrors[] = 'Year level must be an integer from 1 to 4.';
            }

            if ($section === '') {
                $rowErrors[] = 'Missing section.';
            }

            if ($ayKey === '') {
                $rowErrors[] = 'Missing academic year.';
            } elseif (! isset($years[$ayKey])) {
                $rowErrors[] = "Unknown academic year '{$d['academic_year']}'.";
            }

            if ($semName === '') {
                $rowErrors[] = 'Missing semester.';
            }

            if (! is_numeric($gpa) || (float) $gpa < 1 || (float) $gpa > 5) {
                $rowErrors[] = 'GPA must be a number from 1.00 to 5.00.';
            }

            if (! is_numeric($avgGrade) || (float) $avgGrade < 0 || (float) $avgGrade > 100) {
                $rowErrors[] = 'Average grade must be from 0 to 100.';
            }

            if (! is_numeric($attendance) || (float) $attendance < 0 || (float) $attendance > 100) {
                $rowErrors[] = 'Attendance percent must be from 0 to 100.';
            }

            if (! is_numeric($subjects) || (int) $subjects < 0 || (int) $subjects > 20) {
                $rowErrors[] = 'Number of subjects must be from 0 to 20.';
            }

            if (! is_numeric($failed) || (int) $failed < 0) {
                $rowErrors[] = 'Failed subjects must be a non-negative integer.';
            } elseif (is_numeric($subjects) && (int) $failed > (int) $subjects) {
                $rowErrors[] = 'Failed subjects cannot exceed number of subjects.';
            }

            if (! is_numeric($units) || (float) $units < 0 || (float) $units > 36) {
                $rowErrors[] = 'Units enrolled must be from 0 to 36.';
            }

            if ($enrolled === null) {
                $rowErrors[] = "Invalid enrolled status '{$d['enrolled_status']}'.";
            }
            if ($studentType === null) {
                $rowErrors[] = "Invalid student type '{$d['student_type']}'. Use New, Returning, Transferee, or Withdraw Acad / Stop.";
            }
            if ($scholarship === null) {
                $rowErrors[] = "Invalid scholarship status '{$d['scholarship_status']}'.";
            }
            if ($tuition === null) {
                $rowErrors[] = "Invalid tuition status '{$d['tuition_status']}'.";
            }

            $year = $years[$ayKey] ?? null;
            $program = $programs[$course] ?? null;
            $semester = null;
            if ($year && $semName !== '') {
                $semester = $semesters->first(function ($s) use ($year, $semName) {
                    return $s->academic_year_id === $year->id
                        && strcasecmp($s->semester_name, $semName) === 0;
                });
                if (! $semester) {
                    $rowErrors[] = "Semester '{$semName}' is not defined for {$year->academic_year}.";
                }
            }

            $collegeCode = strtoupper(trim((string) ($d['college'] ?? '')));
            if ($collegeCode !== '' && $program) {
                $expected = strtoupper((string) ($program->department?->college?->college_code ?? ''));
                if ($expected !== '' && $collegeCode !== $expected) {
                    $rowErrors[] = "College '{$d['college']}' does not match course {$program->program_code}.";
                }
            }

            $dupKey = implode('|', [$studentId, $ayKey, strtolower($semName)]);
            if ($studentId !== '' && isset($seen[$dupKey])) {
                $rowErrors[] = 'Duplicate Student ID for the same academic year and semester in this file.';
            }
            $seen[$dupKey] = true;

            if ($studentId !== '' && $year && $semester) {
                $exists = StudentRecord::query()->where([
                    'student_id' => $studentId,
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                ])->exists();
                if ($exists) {
                    $rowErrors[] = 'This Student ID already has a record for this academic year and semester.';
                }
            }

            if ($rowErrors) {
                $issues[] = ['line' => $line, 'errors' => $rowErrors, 'data' => $d];
            } else {
                $valid[] = [
                    'student_id' => $studentId,
                    'gender' => $gender,
                    'age' => (int) $age,
                    'program_id' => $program->id,
                    'year_level' => (int) $yearLevel,
                    'section' => $section,
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'gpa' => round((float) $gpa, 2),
                    'average_grade' => round((float) $avgGrade, 2),
                    'attendance_percent' => round((float) $attendance, 2),
                    'subjects_count' => (int) $subjects,
                    'failed_subjects' => (int) $failed,
                    'units_enrolled' => round((float) $units, 1),
                    'enrolled_status' => $enrolled,
                    'student_type' => $studentType,
                    'scholarship_status' => $scholarship,
                    'tuition_status' => $tuition,
                    'line' => $line,
                ];
            }
        }

        $total = max(count($parsedRows), 1);
        $score = round((($total - count($issues)) / $total) * 100, 1);

        return [
            'valid' => $valid,
            'issues' => $issues,
            'score' => $score,
            'counts' => [
                'rows' => count($parsedRows),
                'valid' => count($valid),
                'invalid' => count($issues),
            ],
        ];
    }

    public function commit(array $validRows): int
    {
        $count = 0;
        DB::transaction(function () use ($validRows, &$count) {
            foreach ($validRows as $row) {
                unset($row['line']);
                StudentRecord::create($row);
                $count++;
            }
        });

        return $count;
    }

    private function normalizeHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = str_replace([' ', '-'], '_', $key);

        return $this->headerAliases[$key] ?? $key;
    }

    /**
     * Accept either stored keys (male) or display labels (Male).
     *
     * @param  array<string, string>  $map
     */
    private function mapEnum(string $value, array $map): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $lower = strtolower($raw);
        $slug = strtolower(str_replace([' ', '-'], '_', $raw));

        if (isset($map[$lower])) {
            return $lower;
        }
        if (isset($map[$slug])) {
            return $slug;
        }

        foreach ($map as $key => $label) {
            if (strcasecmp($label, $raw) === 0) {
                return $key;
            }
        }

        return null;
    }

    private function mapStudentType(string $value): ?string
    {
        $mapped = $this->mapEnum($value, StudentRecord::STUDENT_TYPES);
        if ($mapped !== null) {
            return $mapped;
        }

        $slug = strtolower(trim(str_replace([' ', '-', '/'], '_', $value)));
        $aliases = [
            'return' => 'returning',
            'returned' => 'returning',
            'transfer' => 'transferee',
            'transferree' => 'transferee',
            'withdraw' => 'withdraw_stop',
            'withdrawn' => 'withdraw_stop',
            'withdraw_acad' => 'withdraw_stop',
            'withdraw_acad_stop' => 'withdraw_stop',
            'stop' => 'withdraw_stop',
            'stopped' => 'withdraw_stop',
        ];

        return $aliases[$slug] ?? null;
    }
}
