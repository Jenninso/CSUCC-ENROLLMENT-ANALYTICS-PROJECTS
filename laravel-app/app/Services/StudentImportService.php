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
        'studid' => 'student_id',
        'program_code' => 'course',
        'progcode' => 'course',
        'program' => 'course',
        'subjects_count' => 'number_of_subjects',
        'number_of_subject' => 'number_of_subjects',
        'subjects' => 'number_of_subjects',
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
        'sex' => 'gender',
        'sy' => 'academic_year',
        'sem' => 'semester',
        'studlevel' => 'year_level',
        'lastname' => 'last_name',
        'firstname' => 'first_name',
        'middlename' => 'middle_name',
        'extname' => 'ext_name',
        'dateofbirth' => 'date_of_birth',
        'standing' => 'academic_standing',
        'scholarcode' => 'scholar_code',
        'homeaddress' => 'home_address',
        'has_grades' => 'has_numeric_grades',
        'numeric_grades' => 'has_numeric_grades',
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

        // Pre-create any academic years / semesters named in the file so imports stay dynamic.
        foreach ($parsedRows as $row) {
            $ay = trim((string) ($row['data']['academic_year'] ?? ''));
            $sem = $this->normalizeSemesterName(trim((string) ($row['data']['semester'] ?? '')));
            if ($ay !== '' && $sem !== '') {
                $this->ensureAcademicTerm($ay, $sem);
            }
        }

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
            $course = match ($course) {
                'bsinfotech', 'bs_infotech', 'bs information technology' => 'bsit',
                'bscpe', 'bs computer engineering' => 'bscpe',
                'bsee', 'bs electrical engineering' => 'bsee',
                default => $course,
            };
            $yearLevel = $d['year_level'] ?? '';
            $section = strtoupper(trim((string) ($d['section'] ?? '')));
            $ayKey = strtolower(trim((string) ($d['academic_year'] ?? '')));
            $semName = trim((string) ($d['semester'] ?? ''));
            $gpa = $d['gpa'] ?? '';
            $avgGrade = $d['average_grade'] ?? '';
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

            if (! is_numeric($yearLevel) || (int) $yearLevel < 1 || (int) $yearLevel > 5) {
                $rowErrors[] = 'Year level must be an integer from 1 to 5.';
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
                $rowErrors[] = "Invalid student type '{$d['student_type']}'. Use New, Old, Returnee, Returnee-Shiftee, Shiftee, Transferee, or Withdraw Acad / Stop.";
            }
            if ($scholarship === null) {
                $rowErrors[] = "Invalid scholarship status '{$d['scholarship_status']}'.";
            }
            if ($tuition === null) {
                $rowErrors[] = "Invalid tuition status '{$d['tuition_status']}'.";
            }

            // Integrity rules so bad source rows cannot enter the system quietly.
            if ($collegeCode = strtoupper(trim((string) ($d['college'] ?? '')))) {
                if ($collegeCode !== 'CEIT') {
                    $rowErrors[] = "Only CEIT college rows are allowed (got '{$d['college']}').";
                }
            }

            $hasGradesRaw = strtolower(trim((string) ($d['has_numeric_grades'] ?? '')));
            if ($hasGradesRaw !== '') {
                $hasGrades = in_array($hasGradesRaw, ['1', 'true', 'yes', 'y'], true);
                if ($hasGrades && $enrolled === 'dropped') {
                    $rowErrors[] = 'Dropped is not allowed when has_numeric_grades=true (student still has usable grades).';
                }
                if (! $hasGrades && is_numeric($subjects) && (int) $subjects > 0 && $enrolled !== null && $enrolled !== 'dropped') {
                    $rowErrors[] = 'Rows with subjects but no usable grades must use enrolled_status=Dropped.';
                }
            }

            if ($enrolled === 'dropped') {
                if (is_numeric($avgGrade) && (float) $avgGrade > 0) {
                    $rowErrors[] = 'Dropped rows must have average_grade=0 (no usable grades this term).';
                }
                if (is_numeric($gpa) && (float) $gpa < 5) {
                    // Soft-correct later; hard-warn only when clearly contradictory high performance.
                    if ((float) $gpa <= 3) {
                        $rowErrors[] = 'Dropped rows cannot keep a strong GPA leftover from another term. Use GPA 5.00.';
                    }
                }
            }

            $year = $years[$ayKey] ?? null;
            $program = $programs[$course] ?? null;
            $semester = null;
            $normalizedSemester = $this->normalizeSemesterName($semName);
            if ($year && $normalizedSemester !== '') {
                $semester = $semesters->first(function ($s) use ($year, $normalizedSemester) {
                    return $s->academic_year_id === $year->id
                        && strcasecmp($s->semester_name, $normalizedSemester) === 0;
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

            if ($dob = trim((string) ($d['date_of_birth'] ?? ''))) {
                $ts = strtotime($dob);
                if ($ts === false) {
                    $rowErrors[] = "Invalid date_of_birth '{$d['date_of_birth']}'.";
                } elseif ($ts > time()) {
                    $rowErrors[] = 'date_of_birth cannot be in the future.';
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
                $standing = $this->mapEnum((string) ($d['academic_standing'] ?? ''), StudentRecord::ACADEMIC_STANDINGS);
                $dob = trim((string) ($d['date_of_birth'] ?? ''));
                $payload = [
                    'student_id' => $studentId,
                    'last_name' => $this->nullableString($d['last_name'] ?? null, 100),
                    'first_name' => $this->nullableString($d['first_name'] ?? null, 100),
                    'middle_name' => $this->nullableString($d['middle_name'] ?? null, 100),
                    'ext_name' => $this->nullableString($d['ext_name'] ?? null, 30),
                    'email' => $this->nullableString($d['email'] ?? null, 150),
                    'gender' => $gender,
                    'age' => (int) $age,
                    'date_of_birth' => $dob !== '' ? $dob : null,
                    'mobile' => $this->nullableString($d['mobile'] ?? null, 40),
                    'program_id' => $program->id,
                    'year_level' => (int) $yearLevel,
                    'section' => $section,
                    'block' => $this->nullableString($d['block'] ?? null, 40),
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'gpa' => round((float) $gpa, 2),
                    'average_grade' => round((float) $avgGrade, 2),
                    'attendance_percent' => 100.0, // dataset has no attendance; kept only for DB compatibility
                    'subjects_count' => (int) $subjects,
                    'failed_subjects' => (int) $failed,
                    'units_enrolled' => round((float) $units, 1),
                    'enrolled_status' => $enrolled,
                    'student_type' => $studentType,
                    'academic_standing' => $standing,
                    'scholarship_status' => $scholarship,
                    'scholar_code' => $this->nullableString($d['scholar_code'] ?? null, 40),
                    'home_address' => $this->nullableString($d['home_address'] ?? null, 255),
                    'municipality' => $this->nullableString($d['municipality'] ?? null, 100),
                    'province' => $this->nullableString($d['province'] ?? null, 100),
                    'tuition_status' => $tuition,
                    'has_numeric_grades' => $d['has_numeric_grades'] ?? null,
                    'line' => $line,
                ];
                $payload = StudentRecord::applyNoGradeDropRule($payload);
                unset($payload['has_numeric_grades']);
                $valid[] = $payload;
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

    /**
     * @return array{imported: int, skipped: int}
     */
    public function commit(array $validRows): array
    {
        if ($validRows === []) {
            return ['imported' => 0, 'skipped' => 0];
        }

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $imported = 0;
        $skipped = 0;
        $now = now();

        $existing = DB::table('student_records')
            ->select(['student_id', 'academic_year_id', 'semester_id'])
            ->get()
            ->mapWithKeys(fn ($row) => [
                strtoupper((string) $row->student_id).'|'.$row->academic_year_id.'|'.$row->semester_id => true,
            ]);

        DB::transaction(function () use ($validRows, &$imported, &$skipped, &$existing, $now) {
            foreach (array_chunk($validRows, 400) as $chunk) {
                $payload = [];

                foreach ($chunk as $row) {
                    unset($row['line']);

                    $row['student_id'] = strtoupper(trim((string) ($row['student_id'] ?? '')));
                    $row['section'] = strtoupper(trim((string) ($row['section'] ?? '')));
                    $row['risk_level'] = StudentRecord::assessRisk($row);
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;

                    if (array_key_exists('date_of_birth', $row) && blank($row['date_of_birth'])) {
                        $row['date_of_birth'] = null;
                    }

                    $key = $row['student_id'].'|'.$row['academic_year_id'].'|'.$row['semester_id'];
                    if (isset($existing[$key])) {
                        $skipped++;
                        continue;
                    }

                    $existing[$key] = true;
                    $payload[] = $row;
                }

                if ($payload !== []) {
                    DB::table('student_records')->insert($payload);
                    $imported += count($payload);
                }
            }
        });

        if ($imported > 0) {
            app(EnrollmentProjection::class)->rebuildFromStudents();
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Create academic year + semester on demand so Excel uploads stay scalable
     * when new terms appear in the registrar extract.
     */
    public function ensureAcademicTerm(string $academicYear, string $semesterName): void
    {
        $yearLabel = trim($academicYear);
        $semLabel = $this->normalizeSemesterName(trim($semesterName));
        if ($yearLabel === '' || $semLabel === '') {
            return;
        }

        $startYear = 2000;
        if (preg_match('/(20\d{2})\s*[-–]\s*(20\d{2})/', $yearLabel, $m)) {
            $startYear = (int) $m[1];
        }

        $year = AcademicYear::query()->firstOrCreate(
            ['academic_year' => $yearLabel],
            [
                'start_date' => sprintf('%d-08-01', $startYear),
                'end_date' => sprintf('%d-07-31', $startYear + 1),
                'status' => 'inactive',
            ]
        );

        $order = match (true) {
            str_contains(strtolower($semLabel), 'second') => 2,
            str_contains(strtolower($semLabel), 'summer') => 3,
            default => 1,
        };

        Semester::query()->firstOrCreate(
            [
                'academic_year_id' => $year->id,
                'semester_name' => $semLabel,
            ],
            [
                'semester_order' => $order,
                'is_active' => false,
            ]
        );
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
            'new' => 'new',
            'old' => 'returning',
            'returning' => 'returning',
            'return' => 'returnee',
            'returned' => 'returnee',
            'returnee' => 'returnee',
            'shiftee' => 'shiftee',
            'shiftees' => 'shiftee',
            'shift' => 'shiftee',
            'returnee_shiftee' => 'returnee_shiftee',
            'returnee_shiftees' => 'returnee_shiftee',
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

    private function normalizeSemesterName(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $key = strtolower(str_replace(['_', '-'], ' ', $raw));
        $aliases = [
            '1st' => 'First Semester',
            'first' => 'First Semester',
            'first semester' => 'First Semester',
            '2nd' => 'Second Semester',
            'second' => 'Second Semester',
            'second semester' => 'Second Semester',
            'sum' => 'Summer',
            'summer' => 'Summer',
            'midyear' => 'Summer',
            'mid year' => 'Summer',
        ];

        return $aliases[$key] ?? $raw;
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $max);
    }
}
