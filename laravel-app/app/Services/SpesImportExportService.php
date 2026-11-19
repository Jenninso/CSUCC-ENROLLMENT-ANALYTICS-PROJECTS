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
        $missing = array_values(array_diff($required, $header));
        if ($missing) {
            $found = implode(', ', array_values(array_filter($header, fn ($h) => $h !== '')));
            $hint = in_array('birth_date', $missing, true)
                ? ' For birthday use birth_date or date_of_birth (YYYY-MM-DD). Download the SPR template, or Export CSV from SPR.'
                : '';

            return [
                'ok' => false,
                'errors' => [
                    'Missing required columns: '.implode(', ', $missing).'.'
                    .$hint
                    .($found !== '' ? ' Found: '.$found.'.' : ''),
                ],
                'imported' => 0,
                'skipped' => 0,
                'created_ids' => [],
            ];
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $imported = 0;
        $skipped = 0;
        $createdIds = [];
        $errors = [];
        $now = now();
        $batchToken = strtolower(Str::random(10));
        $tempSeq = 0;

        // Preload duplicates + student links once (per-row queries were killing Render on large CSVs).
        $existingByStudentId = [];
        $existingByPerson = [];
        SpesApplication::query()
            ->select(['id', 'student_id', 'last_name', 'first_name', 'middle_name', 'birth_date'])
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$existingByStudentId, &$existingByPerson) {
                foreach ($rows as $row) {
                    $sid = mb_strtolower(trim((string) $row->student_id));
                    if ($sid !== '') {
                        $existingByStudentId[$sid] = true;
                    }
                    $existingByPerson[$this->personKey(
                        $row->last_name,
                        $row->first_name,
                        $row->middle_name,
                        $row->birth_date?->format('Y-m-d')
                    )] = true;
                }
            });

        $studentIdsInFile = [];
        foreach ($matrix as $data) {
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }
            $combined = @array_combine($header, array_slice($data, 0, count($header)));
            if (! is_array($combined)) {
                continue;
            }
            $sid = mb_strtolower(trim((string) ($combined['id_no'] ?? '')));
            if ($sid !== '') {
                $studentIdsInFile[$sid] = true;
            }
        }

        $studentRecordMap = [];
        foreach (array_chunk(array_keys($studentIdsInFile), 500) as $chunk) {
            $records = StudentRecord::query()
                ->select(['id', 'student_id'])
                ->whereIn('student_id', $chunk)
                ->orderByDesc('id')
                ->get();
            foreach ($records as $record) {
                $key = mb_strtolower(trim((string) $record->student_id));
                if ($key !== '' && ! isset($studentRecordMap[$key])) {
                    $studentRecordMap[$key] = (int) $record->id;
                }
            }
        }

        $pending = [];
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
                $payload = $this->rowToApplication($combined, $encodedBy, $studentRecordMap);
            } catch (\InvalidArgumentException $e) {
                if (count($errors) < 50) {
                    $errors[] = "Row {$line}: ".$e->getMessage();
                }
                $skipped++;
                continue;
            }

            $sid = mb_strtolower(trim((string) ($payload['student_id'] ?? '')));
            $person = $this->personKey(
                $payload['last_name'] ?? '',
                $payload['first_name'] ?? '',
                $payload['middle_name'] ?? '',
                $payload['birth_date'] ?? ''
            );
            if (($sid !== '' && isset($existingByStudentId[$sid])) || isset($existingByPerson[$person])) {
                if (count($errors) < 50) {
                    $errors[] = "Row {$line}: skipped — matching SPR already exists.";
                }
                $skipped++;
                continue;
            }

            if ($sid !== '') {
                $existingByStudentId[$sid] = true;
            }
            $existingByPerson[$person] = true;

            $payload['spr_data'] = json_encode($payload['spr_data'] ?? [], JSON_THROW_ON_ERROR);
            $payload['previous_spes'] = ! empty($payload['previous_spes']) ? 1 : 0;
            $payload['student_can_edit'] = 0;
            $payload['has_birth_certificate'] = ! empty($payload['has_birth_certificate']) ? 1 : 0;
            $payload['has_enrollment_cert'] = ! empty($payload['has_enrollment_cert']) ? 1 : 0;
            $payload['has_grades'] = ! empty($payload['has_grades']) ? 1 : 0;
            $payload['has_indigency'] = ! empty($payload['has_indigency']) ? 1 : 0;
            $payload['has_photo'] = ! empty($payload['has_photo']) ? 1 : 0;
            $payload['parents_annual_income'] = $payload['parents_annual_income'] ?? 0;
            // Unique temp refs — reference_no is UNIQUE, so SPR-PENDING cannot be reused in bulk inserts.
            $tempSeq++;
            $payload['reference_no'] = sprintf('SPR-TMP-%s-%05d', $batchToken, $tempSeq);
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            $pending[] = $payload;
            if (count($pending) >= 200) {
                $this->flushInsertChunk($pending, $imported, $createdIds, $batchToken);
                $pending = [];
            }
        }

        if ($pending !== []) {
            $this->flushInsertChunk($pending, $imported, $createdIds, $batchToken);
        }

        // Assign real reference numbers for this batch (driver-aware bulk update).
        $this->assignPendingReferences($batchToken);

        return [
            'ok' => $imported > 0 || $errors === [],
            'errors' => $errors,
            'imported' => $imported,
            'skipped' => $skipped,
            'created_ids' => $createdIds,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     * @param  list<int>  $createdIds
     */
    private function flushInsertChunk(array $chunk, int &$imported, array &$createdIds, string $batchToken): void
    {
        DB::table('spes_applications')->insert($chunk);
        $imported += count($chunk);

        // Keep at most one id for single-row “open form” redirect.
        if (count($createdIds) === 0 && count($chunk) === 1 && $imported === 1) {
            $tempRef = $chunk[0]['reference_no'] ?? null;
            $id = $tempRef
                ? (int) DB::table('spes_applications')->where('reference_no', $tempRef)->value('id')
                : (int) DB::table('spes_applications')
                    ->where('reference_no', 'like', 'SPR-TMP-'.$batchToken.'-%')
                    ->orderByDesc('id')
                    ->value('id');
            if ($id > 0) {
                $createdIds[] = $id;
            }
        }
    }

    private function personKey(mixed $last, mixed $first, mixed $middle, mixed $birth): string
    {
        return mb_strtolower(trim((string) $last)).'|'
            .mb_strtolower(trim((string) $first)).'|'
            .mb_strtolower(trim((string) ($middle ?? ''))).'|'
            .trim((string) ($birth ?? ''));
    }

    private function assignPendingReferences(string $batchToken): void
    {
        $prefix = 'SPR-TMP-'.$batchToken.'-%';
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::update(
                "UPDATE spes_applications
                 SET reference_no = 'SPR-' || application_year::text || '-' || lpad(id::text, 4, '0')
                 WHERE reference_no LIKE ?",
                [$prefix]
            );

            return;
        }

        if ($driver === 'mysql') {
            DB::update(
                "UPDATE spes_applications
                 SET reference_no = CONCAT('SPR-', application_year, '-', LPAD(id, 4, '0'))
                 WHERE reference_no LIKE ?",
                [$prefix]
            );

            return;
        }

        SpesApplication::query()
            ->select(['id', 'application_year'])
            ->where('reference_no', 'like', $prefix)
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $app) {
                    DB::table('spes_applications')->where('id', $app->id)->update([
                        'reference_no' => SpesApplication::referenceFor((int) $app->id, (int) $app->application_year),
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $studentRecordMap
     * @return array<string, mixed>
     */
    private function rowToApplication(array $row, int $encodedBy, array $studentRecordMap = []): array
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
            $studentRecordId = $studentRecordMap[mb_strtolower($studentId)] ?? null;
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

    private function normalizeHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = str_replace([' ', '-'], '_', $key);

        $aliases = [
            'student_id' => 'id_no',
            'studid' => 'id_no',
            'idno' => 'id_no',
            'id' => 'id_no',
            'sex' => 'sex_at_birth',
            'gender' => 'sex_at_birth',
            'sex_at_birth' => 'sex_at_birth',
            'date_of_birth' => 'birth_date',
            'dateofbirth' => 'birth_date',
            'dob' => 'birth_date',
            'birthday' => 'birth_date',
            'bday' => 'birth_date',
            'birthdate' => 'birth_date',
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'firstname' => 'first_name',
            'given_name' => 'first_name',
            'middlename' => 'middle_name',
            'phone' => 'mobile',
            'contact' => 'mobile',
            'contact_number' => 'mobile',
            'course' => 'priority_1',
            'program' => 'priority_1',
            'program_name' => 'priority_1',
            'program_code' => 'priority_1',
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
