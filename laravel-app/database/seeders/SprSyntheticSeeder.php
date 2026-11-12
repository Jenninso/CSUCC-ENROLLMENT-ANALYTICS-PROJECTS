<?php

namespace Database\Seeders;

use App\Models\SpesApplication;
use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SprSyntheticSeeder extends Seeder
{
    public function run(): void
    {
        $latestIds = StudentRecord::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id');

        $students = StudentRecord::query()
            ->with('program')
            ->whereIn('id', $latestIds)
            ->orderBy('student_id')
            ->get();

        $encoderId = User::query()->orderBy('id')->value('id');
        $created = 0;

        foreach ($students as $student) {
            if (SpesApplication::query()->where('student_id', $student->student_id)->exists()) {
                continue;
            }

            $row = $this->application($student, $encoderId);
            $application = SpesApplication::query()->create($row);
            $application->update([
                'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
            ]);
            $created++;
        }

        if ($created === 0 && ! SpesApplication::query()->exists()) {
            $created = $this->seedStandalone();
        }

        if ($this->command) {
            $this->command->info("Synthetic SPR records created: {$created}.");
        }
    }

    private function seedStandalone(): int
    {
        $encoderId = User::query()->orderBy('id')->value('id');
        $programs = ['BS Information Technology', 'BS Computer Engineering', 'BS Electrical Engineering'];
        $statuses = array_keys(SpesApplication::STATUSES);
        $created = 0;

        for ($i = 1; $i <= 72; $i++) {
            $student = new StudentRecord;
            $student->forceFill([
                'student_id' => sprintf('2026-%05d', $i),
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'age' => 17 + ($i % 6),
                'year_level' => ($i % 4) + 1,
                'student_type' => ['new', 'returning', 'transferee'][$i % 3],
                'scholarship_status' => $i % 4 === 0 ? 'government' : 'none',
                'average_grade' => 85 + ($i % 10),
            ]);
            $program = new \App\Models\Program;
            $program->forceFill(['program_name' => $programs[$i % count($programs)]]);
            $student->setRelation('program', $program);

            $application = SpesApplication::query()->create($this->application($student, $encoderId));
            $application->update([
                'reference_no' => SpesApplication::referenceFor($application->id, (int) $application->application_year),
                'status' => $statuses[$i % count($statuses)],
            ]);
            $created++;
        }

        return $created;
    }

    private function application(StudentRecord $student, ?int $encoderId): array
    {
        $n = abs(crc32((string) $student->student_id));
        $pick = fn (array $items) => $items[$n % count($items)];
        $pickAt = fn (array $items, int $shift) => $items[($n + $shift) % count($items)];

        $female = $student->gender === 'female';
        $first = $pick($female ? $this->femaleNames() : $this->maleNames());
        $middle = $pickAt($this->surnames(), 3);
        $last = $pickAt($this->surnames(), 11);
        if ($middle === $last) {
            $last = $pickAt($this->surnames(), 17);
        }

        $city = $pick($this->places());
        $barangay = $pickAt($this->barangays(), 5);
        $province = 'Agusan del Norte';
        $zip = $city === 'Butuan City' ? '8600' : ($city === 'Cabadbaran City' ? '8605' : '8601');
        $birth = $this->birthDate((int) $student->age, $n);
        $program = $student->program?->program_name ?: 'BS Information Technology';
        $others = array_values(array_filter(
            ['BS Information Technology', 'BS Computer Engineering', 'BS Electrical Engineering'],
            fn (string $name) => $name !== $program
        ));
        $admission = match ($student->student_type) {
            'transferee' => 'transferee',
            'new' => 'new_student',
            'withdraw_stop' => 'returnee',
            default => 'old_student',
        };
        $status = ['submitted', 'under_review', 'approved', 'rejected', 'employed', 'completed'][$n % 6];
        $fourPs = $n % 5 === 0;
        $scholarship = $student->scholarship_status !== 'none';
        $support = ['parents'];
        if ($scholarship) {
            $support[] = 'scholarship';
        }
        if ($n % 7 === 0) {
            $support[] = 'student_assistant';
        }
        $track = ['stem', 'ict', 'stem', 'gas', 'abm', 'stem'][$n % 6];
        $entryYear = $this->entryYear($student);
        $school = $pick($this->schools());
        $elementary = $barangay.' Elementary School';
        $incomeBand = $pick(['Below 10,000', '10,001 – 15,000', '15,001 – 20,000', '20,001 – 30,000']);
        $monthly = [8000, 12000, 18000, 25000][$n % 4];
        $father = $pickAt($this->maleNames(), 2).' '.$last;
        $mother = $pickAt($this->femaleNames(), 4).' '.$middle;
        $filed = Carbon::create(2026, ($n % 10) + 1, ($n % 27) + 1);
        $scores = in_array($status, ['approved', 'employed', 'completed'], true);

        $spr = [
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $middle,
            'id_no' => $student->student_id,
            'student_level' => 'undergraduate',
            'admission_type' => $admission,
            'birth_date' => $birth->toDateString(),
            'age' => (int) $student->age,
            'civil_status' => 'Single',
            'sex_at_birth' => $female ? 'Female' : 'Male',
            'sogie' => $female ? 'Female' : 'Male',
            'place_of_birth' => $city,
            'citizenship' => 'Filipino',
            'religion' => $pick(['Roman Catholic', 'Roman Catholic', 'Iglesia ni Cristo', 'Islam', 'Baptist']),
            'home_purok' => 'Purok '.(($n % 8) + 1),
            'home_barangay' => $barangay,
            'home_city' => $city,
            'home_province' => $province,
            'home_zip' => $zip,
            'current_purok' => 'Purok '.(($n % 8) + 1),
            'current_barangay' => $barangay,
            'current_city' => $city,
            'current_province' => $province,
            'current_zip' => $zip,
            'mobile' => '09'.str_pad((string) ($n % 1000000000), 9, '0', STR_PAD_LEFT),
            'ethnicity' => $pick(['Bisaya', 'Cebuano', 'Cebuano', 'Manobo', 'Surigaonon']),
            'email' => strtolower($first.'.'.$last).'.'.preg_replace('/[^a-z0-9]/i', '', $student->student_id).'@student.csucc.edu.ph',
            'first_in_family' => $n % 3 === 0 ? 'yes' : 'no',
            'preferred_hand' => $n % 11 === 0 ? 'left' : 'right',
            'special_needs' => 'no',
            'four_ps' => $fourPs ? 'yes' : 'no',
            'household_id' => $fourPs ? '4PS-'.str_pad((string) ($n % 100000), 5, '0', STR_PAD_LEFT) : '',
            'solo_parent' => 'no',
            'number_of_children' => 0,
            'living_with_parents' => $n % 9 === 0 ? 'no' : 'yes',
            'father_name' => $father,
            'father_status' => $n % 13 === 0 ? 'deceased' : 'living',
            'father_occupation' => $pick(['Farmer', 'Driver', 'Fisherman', 'Construction worker', 'Vendor', 'Security guard']),
            'mother_name' => $mother,
            'mother_status' => 'living',
            'mother_occupation' => $pick(['Housewife', 'Vendor', 'Teacher', 'Farmer', 'Cashier', 'Sewer']),
            'estimated_income' => $incomeBand,
            'support' => $support,
            'scholarship_specify' => $scholarship ? ($student->scholarship_status === 'government' ? 'CHED / government scholarship' : 'Academic scholarship') : '',
            'guardian_name' => $mother,
            'relationship' => 'Mother',
            'guardian_mobile' => '09'.str_pad((string) (($n + 17) % 1000000000), 9, '0', STR_PAD_LEFT),
            'guardian_address' => 'Purok '.(($n % 8) + 1).', '.$barangay.', '.$city,
            'elementary_school' => $elementary,
            'elementary_address' => $barangay.', '.$city,
            'elementary_sy' => ($entryYear - 7).'-'.($entryYear - 6),
            'secondary_school' => $school,
            'secondary_address' => $city.', Agusan del Norte',
            'secondary_sy' => ($entryYear - 1).'-'.$entryYear,
            'last_school' => $admission === 'new_student' ? $school : 'Caraga State University Cabadbaran Campus',
            'last_school_address' => $admission === 'new_student' ? $city.', Agusan del Norte' : 'Cabadbaran City, Agusan del Norte',
            'last_school_years' => $admission === 'new_student' ? ($entryYear - 2).'-'.$entryYear : $entryYear.'-'.($entryYear + max(0, (int) $student->year_level - 1)),
            'shs_track' => $track,
            'preferred_campus' => 'cabadbaran',
            'priority_1' => $program,
            'priority_2' => $others[0] ?? '',
            'priority_3' => $others[1] ?? '',
            'signature_name' => $first.' '.$middle.' '.$last,
            'csu_at_total' => $scores ? (string) (70 + ($n % 25)) : '',
            'csu_at_english' => $scores ? (string) (15 + ($n % 8)) : '',
            'csu_at_math' => $scores ? (string) (14 + ($n % 10)) : '',
            'csu_at_science' => $scores ? (string) (13 + ($n % 9)) : '',
            'csu_at_logical' => $scores ? (string) (12 + ($n % 8)) : '',
            'office_course_1' => $scores ? $program : '',
            'office_course_2' => $scores ? ($others[0] ?? '') : '',
            'office_course_3' => $scores ? ($others[1] ?? '') : '',
            'admission_test_date' => $scores ? Carbon::create(2026, 2, ($n % 20) + 1)->toDateString() : '',
            'admission_year' => $scores ? '2026-2027' : '',
        ];

        return [
            'reference_no' => 'SPR-PENDING-'.$student->student_id,
            'student_record_id' => $student->id,
            'student_id' => $student->student_id,
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $middle,
            'sex' => $female ? 'Female' : 'Male',
            'civil_status' => 'Single',
            'birth_date' => $birth->toDateString(),
            'age' => (int) $student->age,
            'place_of_birth' => $city,
            'address' => $spr['home_purok'],
            'barangay' => $barangay,
            'city_municipality' => $city,
            'province' => $province,
            'zip_code' => $zip,
            'contact_number' => $spr['mobile'],
            'email' => $spr['email'],
            'father_name' => $father,
            'father_occupation' => $spr['father_occupation'],
            'mother_name' => $mother,
            'mother_occupation' => $spr['mother_occupation'],
            'parents_annual_income' => $monthly * 12,
            'number_of_siblings' => $n % 5,
            'education_level' => 'undergraduate',
            'school_name' => $school,
            'school_address' => $spr['secondary_address'],
            'course' => $program,
            'year_level' => SpesApplication::yearLevelFromStudent((int) $student->year_level),
            'general_average' => $student->average_grade,
            'enrollment_status' => $admission,
            'application_year' => 2026,
            'work_period' => 'na',
            'preferred_days' => 0,
            'beneficiary_type' => 'student',
            'previous_spes' => false,
            'preferred_assignment' => null,
            'skills' => $pick(['Computer literacy', 'Basic encoding', 'MS Office', 'Technical drafting', 'None']),
            'has_birth_certificate' => true,
            'has_enrollment_cert' => $n % 4 !== 0,
            'has_grades' => true,
            'has_indigency' => $fourPs || $n % 3 === 0,
            'has_photo' => $n % 6 !== 0,
            'application_date' => $filed->toDateString(),
            'applicant_signature' => $spr['signature_name'],
            'status' => $status,
            'student_can_edit' => false,
            'remarks' => $status === 'rejected' ? 'Incomplete supporting documents.' : ($scores ? 'Synthetic admission record for an existing student.' : null),
            'spr_data' => $spr,
            'encoded_by' => $encoderId,
            'user_id' => null,
            'public_token' => Str::random(40),
        ];
    }

    private function birthDate(int $age, int $n): Carbon
    {
        $month = ($n % 12) + 1;
        $day = ($n % 27) + 1;
        $birth = Carbon::create(now()->year - max($age, 1), $month, $day)->startOfDay();
        if ($birth->age !== $age) {
            $birth = Carbon::create(now()->year - max($age, 1) - 1, $month, $day)->startOfDay();
        }

        return $birth;
    }

    private function entryYear(StudentRecord $student): int
    {
        if (preg_match('/(20\d{2})/', (string) $student->student_id, $match)) {
            return (int) $match[1];
        }

        return (int) now()->year - max(0, (int) $student->year_level - 1);
    }

    /** @return list<string> */
    private function maleNames(): array
    {
        return ['Juan', 'Jose', 'Mark', 'John', 'Carlo', 'Andrei', 'Gabriel', 'Miguel', 'Angelo', 'Paolo', 'Christian', 'Daniel', 'Joshua', 'Ryan', 'Kenneth', 'Adrian', 'Francis', 'Jerome', 'Noel', 'Ramil'];
    }

    /** @return list<string> */
    private function femaleNames(): array
    {
        return ['Maria', 'Ana', 'Grace', 'Joy', 'Kristine', 'Angel', 'Nicole', 'Patricia', 'Jasmine', 'Rose', 'Camille', 'Diane', 'Erica', 'Hannah', 'Louise', 'Michelle', 'Princess', 'Sarah', 'Trisha', 'Bea'];
    }

    /** @return list<string> */
    private function surnames(): array
    {
        return ['Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Ramos', 'Torres', 'Flores', 'Gonzales', 'Bautista', 'Villanueva', 'Castillo', 'Navarro', 'Fernandez', 'Morales', 'Ramirez', 'Aguilar', 'Salazar', 'Pascual', 'Domingo', 'Mercado', 'Velasco', 'Cabrera', 'Ocampo', 'Padilla', 'Santiago', 'Gutierrez', 'Caballero', 'Jamero', 'Quijano'];
    }

    /** @return list<string> */
    private function places(): array
    {
        return ['Cabadbaran City', 'Cabadbaran City', 'Butuan City', 'Nasipit', 'Tubay', 'Magallanes', 'Santiago', 'Jabonga', 'Carmen', 'Buenavista'];
    }

    /** @return list<string> */
    private function barangays(): array
    {
        return ['Comagascas', 'Kauswagan', 'Calamba', 'Tolosa', 'Mahaba', 'Sanghan', 'Del Pilar', 'Katipunan', 'Mabini', 'Poblacion', 'Cabinet', 'Antonio Luna', 'Bay-ang', 'Puting Bato', 'La Union'];
    }

    /** @return list<string> */
    private function schools(): array
    {
        return [
            'Cabadbaran City National High School',
            'Nasipit National Vocational School',
            'Agusan National High School',
            'Butuan City National High School',
            'Tubay National High School',
            'Magallanes National High School',
        ];
    }
}
