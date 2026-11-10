<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public array $requiredColumns = [
        'academic_year',
        'semester',
        'program_code',
        'year_level',
        'enrollment_type',
        'enrollment_count',
    ];

    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['ok' => false, 'errors' => ['Unable to read the uploaded file.'], 'rows' => []];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return ['ok' => false, 'errors' => ['The file is empty.'], 'rows' => []];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $missing = array_diff($this->requiredColumns, $header);
        if ($missing) {
            fclose($handle);

            return [
                'ok' => false,
                'errors' => ['Missing required columns: '.implode(', ', $missing)],
                'rows' => [],
                'header' => $header,
            ];
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }
            $rows[] = ['line' => $line, 'data' => array_combine($header, array_slice($data, 0, count($header)))];
        }
        fclose($handle);

        return ['ok' => true, 'rows' => $rows, 'header' => $header, 'errors' => []];
    }

    public function validate(array $parsedRows): array
    {
        $issues = [];
        $valid = [];
        $seen = [];

        $years = AcademicYear::query()->get()->keyBy(fn ($y) => strtolower($y->academic_year));
        $programs = Program::query()->with('department')->get()->keyBy(fn ($p) => strtolower($p->program_code));
        $semesters = Semester::query()->get();

        $allowedTypes = ['new', 'returning', 'continuing', 'transferee', 'graduating', 'withdrawn', 'dropped'];

        foreach ($parsedRows as $row) {
            $line = $row['line'];
            $d = $row['data'];
            $rowErrors = [];

            $ayKey = strtolower(trim((string) ($d['academic_year'] ?? '')));
            $semName = trim((string) ($d['semester'] ?? ''));
            $code = strtolower(trim((string) ($d['program_code'] ?? '')));
            $yearLevel = $d['year_level'] ?? '';
            $type = strtolower(trim((string) ($d['enrollment_type'] ?? '')));
            $count = $d['enrollment_count'] ?? '';

            if ($ayKey === '') {
                $rowErrors[] = 'Missing academic year.';
            } elseif (! isset($years[$ayKey])) {
                $rowErrors[] = "Unknown academic year '{$d['academic_year']}'.";
            }

            if ($semName === '') {
                $rowErrors[] = 'Missing semester.';
            }

            if ($code === '') {
                $rowErrors[] = 'Missing program code.';
            } elseif (! isset($programs[$code])) {
                $rowErrors[] = "Invalid program code '{$d['program_code']}'.";
            }

            if (! is_numeric($yearLevel) || (int) $yearLevel < 1 || (int) $yearLevel > 6) {
                $rowErrors[] = 'Year level must be an integer from 1 to 6.';
            }

            if (! in_array($type, $allowedTypes, true)) {
                $rowErrors[] = "Invalid enrollment type '{$d['enrollment_type']}'.";
            }

            if ($count === '' || $count === null) {
                $rowErrors[] = 'Missing enrollment count.';
            } elseif (! is_numeric($count)) {
                $rowErrors[] = 'Enrollment count must be numeric.';
            } elseif ((int) $count < 0) {
                $rowErrors[] = 'Negative enrollment counts are not allowed.';
            }

            $year = $years[$ayKey] ?? null;
            $program = $programs[$code] ?? null;
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

            $dupKey = implode('|', [$ayKey, strtolower($semName), $code, (int) $yearLevel, $type]);
            if (isset($seen[$dupKey])) {
                $rowErrors[] = 'Duplicate combination of year, semester, program, year level, and type in this file.';
            }
            $seen[$dupKey] = true;

            if ($year && $semester && $program) {
                $exists = EnrollmentRecord::query()->where([
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'program_id' => $program->id,
                    'year_level' => (int) $yearLevel,
                    'enrollment_type' => $type,
                ])->exists();
                if ($exists) {
                    $rowErrors[] = 'A record with this unique combination already exists in the database.';
                }
            }

            if ($rowErrors) {
                $issues[] = ['line' => $line, 'errors' => $rowErrors, 'data' => $d];
            } else {
                $valid[] = [
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester->id,
                    'college_id' => $program->department->college_id,
                    'department_id' => $program->department_id,
                    'program_id' => $program->id,
                    'year_level' => (int) $yearLevel,
                    'enrollment_type' => $type,
                    'enrollment_count' => (int) $count,
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
                EnrollmentRecord::create($row);
                $count++;
            }
        });

        return $count;
    }
}
