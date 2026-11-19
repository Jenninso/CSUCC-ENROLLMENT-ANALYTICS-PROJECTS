<?php

namespace App\Services;

use App\Models\SpesApplication;
use App\Models\StudentRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpesImportExportService
{
    /** Flat form columns written to / read from CSV or Excel. */
    public const COLUMNS = [
        'reference_no',
        'status',
        'id_no',
        'last_name',
        'first_name',
        'middle_name',
        'birth_date',
        'age',
        'sex_at_birth',
        'civil_status',
        'place_of_birth',
        'citizenship',
        'religion',
        'home_purok',
        'home_barangay',
        'home_city',
        'home_province',
        'home_zip',
        'current_purok',
        'current_barangay',
        'current_city',
        'current_province',
        'current_zip',
        'mobile',
        'email',
        'ethnicity',
        'student_level',
        'admission_type',
        'preferred_campus',
        'first_in_family',
        'preferred_hand',
        'special_needs',
        'special_needs_detail',
        'four_ps',
        'household_id',
        'solo_parent',
        'number_of_children',
        'living_with_parents',
        'father_name',
        'father_status',
        'father_occupation',
        'mother_name',
        'mother_status',
        'mother_occupation',
        'estimated_income',
        'guardian_name',
        'relationship',
        'guardian_mobile',
        'guardian_address',
        'elementary_school',
        'elementary_address',
        'elementary_sy',
        'secondary_school',
        'secondary_address',
        'secondary_sy',
        'shs_track',
        'last_school',
        'last_school_address',
        'last_school_years',
        'baccalaureate_degree',
        'baccalaureate_school',
        'baccalaureate_address',
        'baccalaureate_years',
        'priority_1',
        'priority_2',
        'priority_3',
        'signature_name',
        'application_year',
        'application_date',
        'has_birth_certificate',
        'has_enrollment_cert',
        'has_grades',
        'has_indigency',
        'has_photo',
        'remarks',
    ];

    public function __construct(private SpreadsheetFileReader $reader) {}

    /**
     * @return list<string>
     */
    public function headerRow(): array
    {
        return self::COLUMNS;
    }

    /**
     * @return list<string>
     */
    public function exportRow(SpesApplication $application): array
    {
        $paper = $application->paper();
        $row = [];
        foreach (self::COLUMNS as $column) {
            $row[] = match ($column) {
                'reference_no' => (string) $application->reference_no,
                'status' => (string) $application->status,
                'application_year' => (string) ($application->application_year ?: ''),
                'application_date' => $application->application_date?->format('Y-m-d') ?? '',
                'has_birth_certificate',
                'has_enrollment_cert',
                'has_grades',
                'has_indigency',
                'has_photo' => $application->{$column} ? 'yes' : 'no',
                'remarks' => (string) ($application->remarks ?? ''),
                'support' => is_array($paper['support'] ?? null) ? implode('|', $paper['support']) : (string) ($paper['support'] ?? ''),
                default => $this->cell($paper[$column] ?? ''),
            };
        }

        return $row;
    }

    /**
     * @return array{ok: bool, errors: list<string>, imported: int, skipped: int, created_ids: list<int>}
     */
    public function import(string $path, string $originalName, int $encodedBy): array
    {
        $read = $this->reader->read($path, $originalName);
        if (! $read['ok']) {
            return ['ok' => false, 'errors' => $read['errors'], 'imported' => 0, 'skipped' => 0, 'created_ids' => []];
        }

        $matrix = $read['rows'];
        $headerRow = array_shift($matrix);
        if (! $headerRow) {
            return ['ok' => false, 'errors' => ['The file is empty.'], 'imported' => 0, 'skipped' => 0, 'created_ids' => []];
        }

        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headerRow);
        $required = ['last_name', 'first_name', 'birth_date'];
        $missing = array_diff($required, $header);
        if ($missing) {
            return [
                'ok' => false,
                'errors' => ['Missing required columns: '.implode(', ', $missing)],
                'imported' => 0,
                'skipped' => 0,
                'created_ids' => [],
            ];
        }

        $imported = 0;
        $skipped = 0;
        $createdIds = [];
        $errors = [];

        DB::transaction(function () use ($matrix, $header, $encodedBy, &$imported, &$skipped, &$createdIds, &$errors) {
            $line = 1;
            foreach ($matrix as $data) {
                $line++;
                if (count($data) < count($header)) {
                    $data = array_pad($data, count($header), '');
                }
                $combined = array_combine($header, array_slice($data, 0, count($header)));
                if ($combined === false) {
                    $skipped++;
                    continue;
                }
                if (trim(implode('', $combined)) === '') {
                    continue;
                }

                try {
                    $payload = $this->rowToApplication($combined, $encodedBy);
                } catch (\InvalidArgumentException $e) {
                    $errors[] = "Row {$line}: ".$e->getMessage();
                    $skipped++;
                    continue;
                }

                if ($this->isDuplicate($payload)) {
                    $errors[] = "Row {$line}: skipped — matching SPR already exists.";
                    $skipped++;
                    continue;
                }

                $application = SpesApplication::create($payload);
                $application->update([
                    'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
                ]);
                $createdIds[] = $application->id;
                $imported++;
            }
        });

        return [
            'ok' => $imported > 0 || $errors === [],
            'errors' => $errors,
            'imported' => $imported,
            'skipped' => $skipped,
            'created_ids' => $createdIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowToApplication(array $row, int $encodedBy): array
    {
        $get = fn (string $key, mixed $default = null) => $this->trimOrNull($row[$key] ?? $default);

        $last = $get('last_name');
        $first = $get('first_name');
        $birthRaw = $get('birth_date');
        if ($last === null || $first === null || $birthRaw === null) {
            throw new \InvalidArgumentException('last_name, first_name, and birth_date are required.');
        }

        try {
            $birth = Carbon::parse($birthRaw)->startOfDay();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('birth_date must be a valid date (YYYY-MM-DD).');
        }

        if ($birth->isFuture()) {
            throw new \InvalidArgumentException('birth_date cannot be in the future.');
        }

        $blank = fn ($value) => ($value === null || $value === '') ? 'N/A' : $value;
        $studentId = $get('id_no');
        $signature = $get('signature_name') ?: trim($first.' '.$last);
        $status = strtolower((string) ($get('status') ?: 'submitted'));
        if (! array_key_exists($status, SpesApplication::STATUSES)) {
            $status = 'submitted';
        }

        $spr = [];
        foreach (self::COLUMNS as $column) {
            if (in_array($column, ['reference_no', 'status', 'application_year', 'application_date', 'remarks', 'has_birth_certificate', 'has_enrollment_cert', 'has_grades', 'has_indigency', 'has_photo'], true)) {
                continue;
            }
            $value = $get($column);
            if ($value !== null) {
                $spr[$column] = $value;
            }
        }
        $spr['id_no'] = $studentId;
        $spr['signature_name'] = $signature;
        $spr['last_name'] = $last;
        $spr['first_name'] = $first;
        $spr['birth_date'] = $birth->toDateString();

        $age = $get('age');
        $applicationYear = (int) ($get('application_year') ?: now()->year);
        $applicationDate = $get('application_date');
        try {
            $applicationDate = $applicationDate ? Carbon::parse($applicationDate)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            $applicationDate = now()->toDateString();
        }

        $studentRecordId = null;
        if ($studentId) {
            $studentRecordId = StudentRecord::query()
                ->where('student_id', $studentId)
                ->latest('id')
                ->value('id');
        }

        return [
            'reference_no' => 'SPR-PENDING',
            'student_record_id' => $studentRecordId,
            'student_id' => $studentId,
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $get('middle_name'),
            'sex' => $blank($get('sex_at_birth')),
            'civil_status' => $blank($get('civil_status')),
            'birth_date' => $birth->toDateString(),
            'age' => $age !== null && is_numeric($age) ? (int) $age : $birth->age,
            'place_of_birth' => $blank($get('place_of_birth')),
            'address' => $blank($get('home_purok')),
            'barangay' => $blank($get('home_barangay')),
            'city_municipality' => $blank($get('home_city')),
            'province' => $blank($get('home_province')),
            'zip_code' => $get('home_zip'),
            'contact_number' => $blank($get('mobile')),
            'email' => $get('email'),
            'father_name' => $get('father_name'),
            'father_occupation' => $get('father_occupation'),
            'mother_name' => $get('mother_name'),
            'mother_occupation' => $get('mother_occupation'),
            'parents_annual_income' => 0,
            'number_of_siblings' => (int) ($get('number_of_children') ?: 0),
            'education_level' => $get('student_level') ?: 'undergraduate',
            'school_name' => $blank($get('secondary_school') ?: $get('elementary_school')),
            'school_address' => $get('secondary_address'),
            'course' => $get('priority_1'),
            'year_level' => 'N/A',
            'enrollment_status' => $get('admission_type') ?: 'new_student',
            'application_year' => $applicationYear,
            'work_period' => 'na',
            'preferred_days' => 0,
            'beneficiary_type' => 'student',
            'previous_spes' => false,
            'application_date' => $applicationDate,
            'applicant_signature' => $signature,
            'status' => $status,
            'student_can_edit' => false,
            'remarks' => $get('remarks'),
            'spr_data' => $spr,
            'encoded_by' => $encodedBy,
            'user_id' => null,
            'public_token' => Str::random(40),
            'has_birth_certificate' => $this->truthy($get('has_birth_certificate')),
            'has_enrollment_cert' => $this->truthy($get('has_enrollment_cert')),
            'has_grades' => $this->truthy($get('has_grades')),
            'has_indigency' => $this->truthy($get('has_indigency')),
            'has_photo' => $this->truthy($get('has_photo')),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isDuplicate(array $payload): bool
    {
        $studentId = mb_strtolower(trim((string) ($payload['student_id'] ?? '')));
        if ($studentId !== '' && SpesApplication::query()
            ->whereRaw('lower(trim(student_id)) = ?', [$studentId])
            ->exists()) {
            return true;
        }

        return SpesApplication::query()
            ->whereRaw('lower(trim(last_name)) = ?', [mb_strtolower(trim((string) $payload['last_name']))])
            ->whereRaw('lower(trim(first_name)) = ?', [mb_strtolower(trim((string) $payload['first_name']))])
            ->whereRaw("lower(trim(coalesce(middle_name, ''))) = ?", [mb_strtolower(trim((string) ($payload['middle_name'] ?? '')))])
            ->whereDate('birth_date', $payload['birth_date'])
            ->exists();
    }

    private function normalizeHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = str_replace([' ', '-'], '_', $key);

        $aliases = [
            'student_id' => 'id_no',
            'studid' => 'id_no',
            'sex' => 'sex_at_birth',
            'gender' => 'sex_at_birth',
            'phone' => 'mobile',
            'contact' => 'mobile',
            'contact_number' => 'mobile',
            'course' => 'priority_1',
            'program' => 'priority_1',
        ];

        return $aliases[$key] ?? $key;
    }

    private function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '' || strcasecmp($text, 'N/A') === 0) {
            return null;
        }

        return $text;
    }

    private function truthy(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        $text = strtolower(trim((string) $value));

        return in_array($text, ['1', 'yes', 'y', 'true', 'x'], true);
    }

    private function cell(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        if (is_array($value)) {
            return implode('|', array_map(fn ($v) => (string) $v, $value));
        }
        $text = trim((string) $value);

        return strcasecmp($text, 'N/A') === 0 ? '' : $text;
    }
}
