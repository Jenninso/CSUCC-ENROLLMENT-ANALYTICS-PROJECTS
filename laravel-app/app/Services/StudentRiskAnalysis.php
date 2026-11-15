<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\EnrollmentRecord;
use App\Models\SpesApplication;
use App\Models\StudentIntervention;
use App\Models\StudentRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentRiskAnalysis
{
    /** Assumed class meetings in one term, used only to estimate absences. */
    public const TERM_MEETINGS = 54;

    public const LEVELS = ['low', 'moderate', 'high', 'critical'];

    public function __construct(
        private ?SubjectCurriculumService $curriculum = null
    ) {
        $this->curriculum ??= app(SubjectCurriculumService::class);
    }

    /**
     * Latest academic term for every student, with SPR context attached.
     *
     * @return array{students: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function cohort(): array
    {
        $records = StudentRecord::query()
            ->with(['program', 'academicYear', 'semester'])
            ->get()
            ->groupBy('student_id');

        $sprs = SpesApplication::query()
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->keyBy(fn (SpesApplication $spr) => strtoupper((string) $spr->student_id));

        $students = [];
        foreach ($records as $studentId => $history) {
            $ordered = $this->ordered($history);
            $current = $ordered->last();
            if (! $current) {
                continue;
            }
            $students[] = $this->forRecord($current, $ordered, $sprs->get(strtoupper((string) $studentId)));
        }

        usort($students, function (array $a, array $b): int {
            $rank = $this->rank($b['level']) <=> $this->rank($a['level']);

            return $rank !== 0 ? $rank : strcmp($a['student_id'], $b['student_id']);
        });

        return [
            'students' => $students,
            'summary' => $this->summarize($students),
        ];
    }

    /**
     * Summary plus one page of students. Avoids building a full report and a huge HTML table for every record.
     *
     * @return array{students: LengthAwarePaginator, summary: array<string, mixed>, programs: Collection<int|string, string>}
     */
    public function browse(Request $request): array
    {
        $records = DB::table('student_records as students')
            ->join('programs', 'programs.id', '=', 'students.program_id')
            ->orderByDesc('students.id')
            ->get([
                'students.id',
                'students.student_id',
                'students.program_id',
                'students.year_level',
                'students.section',
                'students.gpa',
                'students.attendance_percent',
                'students.failed_subjects',
                'students.enrolled_status',
                'students.student_type',
                'programs.program_code',
            ]);

        $latest = [];
        foreach ($records as $record) {
            $key = strtoupper((string) $record->student_id);
            if (! isset($latest[$key])) {
                $latest[$key] = $this->lightRow($record);
            }
        }

        $students = array_values($latest);
        usort($students, function (array $a, array $b): int {
            $rank = $this->rank($b['level']) <=> $this->rank($a['level']);

            return $rank !== 0 ? $rank : strcmp($a['student_id'], $b['student_id']);
        });

        $programs = collect($students)->pluck('program', 'program_id')->filter();
        $filtered = array_values(array_filter($students, function (array $row) use ($request): bool {
            $search = trim((string) $request->query('q', ''));
            if ($search !== '' && ! str_contains(strtolower($row['student_id']), strtolower($search))) {
                return false;
            }
            if ($request->filled('level') && $row['level'] !== $request->query('level')) {
                return false;
            }
            if ($request->filled('year_level') && $row['year_level'] !== $request->integer('year_level')) {
                return false;
            }
            if ($request->filled('program_id') && $row['program_id'] !== $request->integer('program_id')) {
                return false;
            }

            return true;
        }));

        $perPage = 25;
        $page = max(1, $request->integer('page', 1));
        $pageItems = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        return [
            'students' => new Paginator($pageItems, count($filtered), $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
            'summary' => $this->summarize($students),
            'programs' => $programs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lightRow(object $record): array
    {
        $level = StudentRecord::assessRisk([
            'gpa' => $record->gpa,
            'attendance_percent' => $record->attendance_percent,
            'failed_subjects' => $record->failed_subjects,
            'enrolled_status' => $record->enrolled_status,
            'student_type' => $record->student_type,
        ]);

        $struggling = '';
        if ((int) $record->failed_subjects > 0) {
            $subData = $this->curriculum->forStudentRecord($record);
            $struggling = $subData['summary']['struggling_subjects_text'] ?? '';
        }

        return [
            'student_record_id' => (int) $record->id,
            'student_id' => (string) $record->student_id,
            'program' => (string) $record->program_code,
            'program_id' => (int) $record->program_id,
            'year_level' => (int) $record->year_level,
            'year_label' => EnrollmentRecord::yearLevelLabel((int) $record->year_level),
            'section' => (string) $record->section,
            'gpa' => (float) $record->gpa,
            'attendance' => (float) $record->attendance_percent,
            'failed_subjects' => (int) $record->failed_subjects,
            'struggling_subjects' => $struggling,
            'level' => $level,
            'level_label' => StudentRecord::RISK_LEVELS[$level] ?? ucfirst($level),
            'enrolled' => StudentRecord::ENROLLED_STATUSES[$record->enrolled_status] ?? ucfirst((string) $record->enrolled_status),
            'declining_grades' => false,
            'frequent_absences' => (float) $record->attendance_percent < 85,
            'improved' => false,
            'factor_keys' => [],
        ];
    }

    /**
     * @param  Collection<int, StudentRecord>|null  $history
     * @return array<string, mixed>
     */
    public function forStudent(StudentRecord $current, ?Collection $history = null, ?SpesApplication $spr = null): array
    {
        $history ??= StudentRecord::query()
            ->with(['program', 'academicYear', 'semester'])
            ->where('student_id', $current->student_id)
            ->get();

        $spr ??= SpesApplication::query()
            ->where('student_id', $current->student_id)
            ->latest('id')
            ->first();

        $current->loadMissing(['program', 'academicYear', 'semester']);

        return $this->forRecord($current, $history, $spr);
    }

    /**
     * @param  Collection<int, StudentRecord>  $history
     * @return array<string, mixed>
     */
    public function forRecord(StudentRecord $current, Collection $history, ?SpesApplication $spr): array
    {
        $ordered = $this->ordered($history);
        if ($ordered->doesntContain(fn (StudentRecord $row) => $row->id === $current->id)) {
            $ordered->push($current);
            $ordered = $this->ordered($ordered);
        }

        $previous = $ordered->count() > 1 ? $ordered[$ordered->count() - 2] : null;
        $academicLevel = StudentRecord::assessRisk($current->getAttributes());
        $gpa = (float) $current->gpa;
        $previousGpa = $previous ? (float) $previous->gpa : null;
        $attendance = (float) $current->attendance_percent;
        $previousAttendance = $previous ? (float) $previous->attendance_percent : null;
        $gpaWorse = $previousGpa !== null ? round($gpa - $previousGpa, 2) : 0.0;
        $attendanceChange = $previousAttendance !== null ? round($attendance - $previousAttendance, 2) : 0.0;
        $decliningGrades = $gpaWorse >= 0.25;
        $decliningAttendance = $attendanceChange <= -5;
        $sharpDecline = $gpaWorse >= 0.50 || $attendanceChange <= -10;
        $level = $sharpDecline ? $this->raise($academicLevel) : $academicLevel;
        $failed = (int) $current->failed_subjects;
        $absences = (int) round(self::TERM_MEETINGS * max(0, 100 - $attendance) / 100);
        $frequentAbsences = $attendance < 85;

        $indicators = $this->indicators($current, $decliningGrades, $frequentAbsences, $absences, $gpaWorse);
        $context = $this->context($current, $spr);
        $recommendations = $this->recommendations($level, $current, $decliningGrades, $frequentAbsences, $context['keys']);

        $previousLevel = $previous ? StudentRecord::assessRisk($previous->getAttributes()) : null;
        $improved = $previousLevel !== null
            && $this->rank($previousLevel) >= $this->rank('moderate')
            && ($gpaWorse <= -0.25 || $attendanceChange >= 5);

        return [
            'student_record_id' => $current->id,
            'student_id' => $current->student_id,
            'name' => null,
            'program' => $current->program?->program_code,
            'program_name' => $current->program?->program_name,
            'program_id' => $current->program_id,
            'year_level' => (int) $current->year_level,
            'year_label' => EnrollmentRecord::yearLevelLabel((int) $current->year_level),
            'section' => $current->section,
            'term' => trim(($current->academicYear?->academic_year ?? '').' '.($current->semester?->semester_name ?? '')),
            'enrolled' => $current->enrolledLabel(),
            'student_type' => $current->studentTypeLabel(),
            'level' => $level,
            'level_label' => StudentRecord::RISK_LEVELS[$level] ?? ucfirst($level),
            'academic_level' => $academicLevel,
            'gpa' => $gpa,
            'previous_gpa' => $previousGpa,
            'gpa_change' => $gpaWorse,
            'average_grade' => (float) $current->average_grade,
            'attendance' => $attendance,
            'previous_attendance' => $previousAttendance,
            'attendance_change' => $attendanceChange,
            'estimated_absences' => $absences,
            'failed_subjects' => $failed,
            'subjects_count' => (int) $current->subjects_count,
            'declining_grades' => $decliningGrades,
            'frequent_absences' => $frequentAbsences,
            'improved' => $improved,
            'indicators' => $indicators,
            'factors' => $context['factors'],
            'factor_keys' => $context['keys'],
            'spr' => $context['spr'],
            'narrative' => $this->narrative($level, $academicLevel, $sharpDecline, $indicators, $context['factors']),
            'recommendations' => $recommendations,
            'subject_analysis' => $this->curriculum->forStudentRecord($current),
            'history' => $ordered->map(fn (StudentRecord $row) => [
                'id' => $row->id,
                'term' => trim(($row->academicYear?->academic_year ?? '').' '.($row->semester?->semester_name ?? '')),
                'gpa' => (float) $row->gpa,
                'average_grade' => (float) $row->average_grade,
                'attendance' => (float) $row->attendance_percent,
                'failed_subjects' => (int) $row->failed_subjects,
                'enrolled' => $row->enrolledLabel(),
                'level' => StudentRecord::assessRisk($row->getAttributes()),
            ])->values()->all(),
            'warnings' => $this->warnings($current),
        ];
    }

    public function raiseWarnings(): int
    {
        $latestIds = DB::table('student_records')
            ->selectRaw('MAX(id) as id')
            ->groupByRaw('UPPER(student_id)');

        $rows = DB::table('student_records')
            ->joinSub($latestIds, 'latest', 'latest.id', '=', 'student_records.id')
            ->where(function ($query): void {
                $query->where('gpa', '>=', 2.75)
                    ->orWhere('attendance_percent', '<', 75)
                    ->orWhere('failed_subjects', '>=', 2)
                    ->orWhereIn('enrolled_status', ['dropped', 'withdrawn'])
                    ->orWhere('student_type', 'withdraw_stop');
            })
            ->get([
                'student_records.student_id',
                'student_records.program_id',
                'student_records.gpa',
                'student_records.attendance_percent',
                'student_records.failed_subjects',
                'student_records.enrolled_status',
                'student_records.student_type',
            ]);

        $recent = array_fill_keys(
            Alert::query()
                ->where('metric', 'student_risk')
                ->where('created_at', '>=', now()->subDays(14))
                ->pluck('title')
                ->all(),
            true
        );

        $now = now();
        $batch = [];
        $created = 0;
        foreach ($rows as $row) {
            $title = 'At-risk warning: '.$row->student_id;
            if (isset($recent[$title])) {
                continue;
            }

            $level = StudentRecord::assessRisk([
                'gpa' => $row->gpa,
                'attendance_percent' => $row->attendance_percent,
                'failed_subjects' => $row->failed_subjects,
                'enrolled_status' => $row->enrolled_status,
                'student_type' => $row->student_type,
            ]);
            $batch[] = [
                'title' => $title,
                'program_id' => $row->program_id,
                'metric' => 'student_risk',
                'current_value' => $row->gpa,
                'expected_value' => 2.75,
                'severity' => in_array($level, ['critical', 'high'], true) ? $level : 'medium',
                'explanation' => $row->student_id.' crossed a warning threshold. '.implode(' ', $this->warningLines($row)),
                'recommended_action' => $this->warningAction(
                    (float) $row->gpa,
                    (float) $row->attendance_percent,
                    (int) $row->failed_subjects,
                    $level
                ),
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $created++;
            if (count($batch) === 400) {
                Alert::query()->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            Alert::query()->insert($batch);
        }

        return $created;
    }

    /**
     * @return list<string>
     */
    private function warningLines(object $row): array
    {
        $items = [];
        if ((float) $row->gpa >= 2.75) {
            $items[] = 'GPA '.$this->num($row->gpa).' reached the 2.75 warning threshold.';
        }
        if ((float) $row->attendance_percent < 75) {
            $items[] = 'Attendance '.$this->num($row->attendance_percent).'% is below the 75% warning threshold.';
        }
        if ((int) $row->failed_subjects >= 2) {
            $items[] = (int) $row->failed_subjects.' failed subjects reached the warning threshold of 2.';
        }
        if (in_array($row->enrolled_status, ['dropped', 'withdrawn'], true) || $row->student_type === 'withdraw_stop') {
            $label = StudentRecord::ENROLLED_STATUSES[$row->enrolled_status] ?? ucfirst((string) $row->enrolled_status);
            $items[] = 'The student is '.$label.'.';
        }

        return $items;
    }

    private function warningAction(float $gpa, float $attendance, int $failed, string $level): string
    {
        if ($gpa >= 2.25 || $failed > 0) {
            return 'Academic tutoring and remedial classes';
        }
        if ($attendance < 85) {
            return 'Attendance monitoring and follow-up';
        }
        if (in_array($level, ['high', 'critical'], true)) {
            return 'Guidance counseling and student consultation';
        }

        return 'Review the student at-risk report and record a follow-up.';
    }

    /**
     * @param  Collection<int, StudentRecord>  $history
     * @return Collection<int, StudentRecord>
     */
    private function ordered(Collection $history): Collection
    {
        return $history
            ->sortBy(fn (StudentRecord $row) => sprintf(
                '%s-%02d-%010d',
                $row->academicYear?->academic_year ?? '',
                (int) ($row->semester?->semester_order ?? 0),
                $row->id
            ))
            ->values();
    }

    /**
     * @return list<string>
     */
    private function indicators(StudentRecord $current, bool $decliningGrades, bool $frequentAbsences, int $absences, float $gpaWorse): array
    {
        $items = [];
        $gpa = (float) $current->gpa;
        $failed = (int) $current->failed_subjects;

        if ($gpa > 3) {
            $items[] = 'GPA '.$this->num($gpa).' is in the failing range for the term.';
        } elseif ($gpa >= 2.75) {
            $items[] = 'GPA '.$this->num($gpa).' is poor for the current term.';
        } elseif ($gpa >= 2.25) {
            $items[] = 'GPA '.$this->num($gpa).' is below a satisfactory standing.';
        }

        if ($decliningGrades) {
            $items[] = 'GPA worsened by '.$this->num($gpaWorse).' from the previous term. Subject-level grades are not stored, so the decline is read from term GPA and average grade.';
        }

        if ($failed > 0) {
            $items[] = $failed.' failed '.($failed === 1 ? 'subject' : 'subjects').' out of '.(int) $current->subjects_count.'.';
        }

        if ($frequentAbsences) {
            $items[] = 'Attendance is '.$this->num($current->attendance_percent).'%, about '.$absences.' absences in a '.self::TERM_MEETINGS.'-meeting term. Tardiness is not recorded.';
        }

        if (in_array($current->enrolled_status, ['dropped', 'withdrawn', 'on_leave', 'not_enrolled'], true) || $current->student_type === 'withdraw_stop') {
            $items[] = 'Enrollment standing is '.$current->enrolledLabel().' ('.$current->studentTypeLabel().').';
        }

        if ($items === []) {
            $items[] = 'GPA '.$this->num($gpa).', attendance '.$this->num($current->attendance_percent).'%, and no failed subjects.';
        }

        return $items;
    }

    /**
     * @return array{keys: list<string>, factors: list<string>, spr: ?array<string, string>}
     */
    private function context(StudentRecord $current, ?SpesApplication $spr): array
    {
        $keys = [];
        $factors = [];
        if (in_array($current->tuition_status, ['unpaid', 'partial'], true)) {
            $keys[] = 'unpaid_tuition';
            $factors[] = 'Tuition for this term is '.$current->tuitionLabel().'. This is recorded as a financial circumstance, not as an academic risk score.';
        }

        if (! $spr) {
            return ['keys' => $keys, 'factors' => $factors, 'spr' => null];
        }

        $data = $spr->spr_data ?? [];
        $support = is_array($data['support'] ?? null) ? $data['support'] : [];
        $relationship = strtolower((string) ($data['relationship'] ?? ''));
        $lowIncome = ($data['four_ps'] ?? '') === 'yes'
            || ((float) $spr->parents_annual_income > 0 && (float) $spr->parents_annual_income < 150000)
            || str_contains(strtolower((string) ($data['estimated_income'] ?? '')), 'below')
            || str_starts_with(strtolower((string) ($data['estimated_income'] ?? '')), '10,');
        $working = in_array('employed', $support, true) || in_array('student_assistant', $support, true);
        $solo = ($data['solo_parent'] ?? '') === 'yes';
        $grandparent = str_contains($relationship, 'grand') || str_contains($relationship, 'lola') || str_contains($relationship, 'lolo');
        $away = ($data['living_with_parents'] ?? '') === 'no';
        $deceased = ($data['father_status'] ?? '') === 'deceased' || ($data['mother_status'] ?? '') === 'deceased';

        if ($lowIncome) {
            $keys[] = 'low_income';
            $factors[] = 'Household income is reported as '.($data['estimated_income'] ?: 'limited').', about '.number_format((float) $spr->parents_annual_income, 0).' pesos a year.';
        }
        if (($data['four_ps'] ?? '') === 'yes') {
            $keys[] = 'four_ps';
            $factors[] = 'The SPR marks the student as a 4Ps beneficiary.';
        }
        if ($working) {
            $keys[] = 'working_student';
            $factors[] = 'The student is a working student or student assistant and may be balancing employment with classes.';
        }
        if ($solo) {
            $keys[] = 'solo_parent';
            $factors[] = 'The SPR reports a solo-parent household.';
        }
        if ($grandparent) {
            $keys[] = 'grandparent';
            $factors[] = 'The student is recorded as living in a grandparent-led household.';
        }
        if ($away && ! $grandparent) {
            $keys[] = 'not_with_parents';
            $factors[] = 'The student is not living with parents.';
        }
        if ($deceased) {
            $keys[] = 'deceased_parent';
            $factors[] = 'A parent is recorded as deceased.';
        }

        $household = match (true) {
            $grandparent => 'Grandparent-led household',
            $solo => 'Solo-parent household',
            $away => 'Not living with parents',
            default => 'Living with parents',
        };

        return [
            'keys' => $keys,
            'factors' => $factors,
            'spr' => [
                'household' => $household,
                'father' => trim(($spr->father_occupation ?: 'Occupation not given').' · '.ucfirst((string) ($data['father_status'] ?? 'status not given'))),
                'mother' => trim(($spr->mother_occupation ?: 'Occupation not given').' · '.ucfirst((string) ($data['mother_status'] ?? 'status not given'))),
                'income' => ($data['estimated_income'] ?? '') !== '' ? (string) $data['estimated_income'] : 'Not given',
                'annual_income' => number_format((float) $spr->parents_annual_income, 2),
                'working' => $working ? 'Yes' : 'No',
                'solo_parent' => $solo ? 'Yes' : 'No',
                'four_ps' => ($data['four_ps'] ?? '') === 'yes' ? 'Yes' : 'No',
                'address' => trim(implode(', ', array_filter([$spr->address, $spr->barangay, $spr->city_municipality, $spr->province]))),
            ],
        ];
    }

    /**
     * @param  list<string>  $indicators
     * @param  list<string>  $factors
     */
    private function narrative(string $level, string $academicLevel, bool $sharpDecline, array $indicators, array $factors): string
    {
        $label = StudentRecord::RISK_LEVELS[$level] ?? ucfirst($level);
        if ($level === 'low') {
            $text = 'Risk level: Low. Academic performance, attendance, and failed subjects do not show a current academic difficulty. '.implode(' ', $indicators);
            if ($factors !== []) {
                $text .= ' SPR circumstances are on file ('.count($factors).') and were not used to raise this classification.';
            }

            return $text;
        }

        $text = 'Risk level: '.$label.'. The classification comes from academic indicators: '.implode(' ', $indicators);
        if ($sharpDecline && $level !== $academicLevel) {
            $text .= ' The change from the previous term raised the classification by one step.';
        }
        if ($factors !== []) {
            $text .= ' Possible contributing circumstances, which do not by themselves make a student academically at risk: '.implode(' ', $factors);
            $text .= ' These circumstances should be verified with the student, a teacher, or guidance personnel before any support decision.';
        } else {
            $text .= ' No SPR circumstance in the file currently suggests an additional contributing factor. Follow-up can still uncover a situation that was not written on the form.';
        }

        return $text;
    }

    /**
     * @param  list<string>  $factorKeys
     * @return list<array{title: string, detail: string}>
     */
    private function recommendations(string $level, StudentRecord $current, bool $decliningGrades, bool $frequentAbsences, array $factorKeys): array
    {
        if ($level === 'low') {
            return [[
                'title' => 'Continue regular monitoring',
                'detail' => 'No intervention is indicated from the current academic record. Review the next grading period for a drop in GPA or attendance.',
            ]];
        }

        $items = [];
        if ($decliningGrades || (float) $current->gpa >= 2.25 || (int) $current->failed_subjects > 0) {
            $items[] = [
                'title' => 'Academic tutoring and remedial classes',
                'detail' => 'Arrange subject help for the failed or declining work before the next grading period.',
            ];
        }
        if ($frequentAbsences || (float) $current->attendance_percent < 85) {
            $items[] = [
                'title' => 'Attendance monitoring and follow-up',
                'detail' => 'Check absences with the student and record whether they continue after the follow-up.',
            ];
        }
        if (in_array($level, ['high', 'critical'], true)) {
            $items[] = [
                'title' => 'Guidance counseling and student consultation',
                'detail' => 'A counselor should talk with the student about what is getting in the way of classes.',
            ];
        }
        if (array_intersect($factorKeys, ['low_income', 'four_ps', 'unpaid_tuition'])) {
            $items[] = [
                'title' => 'Financial assistance or scholarship referral',
                'detail' => 'Refer only after confirming the reported income or unpaid tuition. Income alone is not an academic finding.',
            ];
        }
        if (array_intersect($factorKeys, ['solo_parent', 'grandparent', 'not_with_parents', 'deceased_parent'])) {
            $items[] = [
                'title' => 'Coordination with parent or guardian',
                'detail' => 'Contact the guardian named on the SPR and confirm the living arrangement.',
            ];
        }
        if (in_array('working_student', $factorKeys, true)) {
            $items[] = [
                'title' => 'Flexible academic support for a working student',
                'detail' => 'Ask about work hours and offer consultation or remedial time that fits that schedule.',
            ];
        }
        $items[] = [
            'title' => 'Referral to a school support program',
            'detail' => 'Record whatever support is actually provided, then compare the next term GPA and attendance with this term.',
        ];

        return $items;
    }

    /**
     * @return list<string>
     */
    private function warnings(StudentRecord $current): array
    {
        $items = [];
        if ((float) $current->gpa >= 2.75) {
            $items[] = 'GPA '.$this->num($current->gpa).' reached the 2.75 warning threshold.';
        }
        if ((float) $current->attendance_percent < 75) {
            $items[] = 'Attendance '.$this->num($current->attendance_percent).'% is below the 75% warning threshold.';
        }
        if ((int) $current->failed_subjects >= 2) {
            $items[] = (int) $current->failed_subjects.' failed subjects reached the warning threshold of 2.';
        }
        if (in_array($current->enrolled_status, ['dropped', 'withdrawn'], true) || $current->student_type === 'withdraw_stop') {
            $items[] = 'The student is '.$current->enrolledLabel().'.';
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @return array<string, mixed>
     */
    private function summarize(array $students): array
    {
        $counts = array_fill_keys(self::LEVELS, 0);
        $byYear = [];
        $bySection = [];
        $factors = [
            'low_income' => 0,
            'working_student' => 0,
            'solo_parent' => 0,
            'grandparent' => 0,
            'not_with_parents' => 0,
            'four_ps' => 0,
            'unpaid_tuition' => 0,
        ];
        $indicators = [
            'Declining grades' => 0,
            'Frequent absences' => 0,
            'Failed subjects' => 0,
            'Poor GPA' => 0,
            'Left or stopped' => 0,
        ];
        $atRisk = 0;
        $declining = 0;
        $absences = 0;
        $improved = 0;
        $enrolled = 0;

        foreach ($students as $student) {
            $counts[$student['level']] = ($counts[$student['level']] ?? 0) + 1;
            $year = $student['year_label'];
            $section = $student['program'].' '.$year.' '.$student['section'];
            $byYear[$year][$student['level']] = ($byYear[$year][$student['level']] ?? 0) + 1;
            $bySection[$section] = ($bySection[$section] ?? 0) + ($student['level'] === 'low' ? 0 : 1);
            if ($student['enrolled'] === 'Enrolled') {
                $enrolled++;
            }
            if ($student['declining_grades']) {
                $declining++;
            }
            if ($student['frequent_absences']) {
                $absences++;
            }
            if ($student['improved']) {
                $improved++;
            }
            if ($student['level'] === 'low') {
                continue;
            }
            $atRisk++;
            foreach (array_keys($factors) as $key) {
                if (in_array($key, $student['factor_keys'], true)) {
                    $factors[$key]++;
                }
            }
            if ($student['declining_grades']) {
                $indicators['Declining grades']++;
            }
            if ($student['frequent_absences']) {
                $indicators['Frequent absences']++;
            }
            if ($student['failed_subjects'] > 0) {
                $indicators['Failed subjects']++;
            }
            if ($student['gpa'] >= 2.75) {
                $indicators['Poor GPA']++;
            }
            if (in_array($student['enrolled'], ['Dropped', 'Withdrawn', 'On Leave', 'Not Enrolled'], true)) {
                $indicators['Left or stopped']++;
            }
        }

        ksort($bySection);
        $outcomes = StudentIntervention::query()
            ->selectRaw('outcome, count(*) as n')
            ->groupBy('outcome')
            ->pluck('n', 'outcome');

        return [
            'total' => count($students),
            'enrolled' => $enrolled,
            'counts' => $counts,
            'at_risk' => $atRisk,
            'declining' => $declining,
            'frequent_absences' => $absences,
            'improved' => $improved,
            'by_year' => $byYear,
            'by_section' => $bySection,
            'factors' => $factors,
            'indicators' => $indicators,
            'outcomes' => [
                'pending' => (int) ($outcomes['pending'] ?? 0),
                'improved' => (int) ($outcomes['improved'] ?? 0),
                'unchanged' => (int) ($outcomes['unchanged'] ?? 0),
                'worsened' => (int) ($outcomes['worsened'] ?? 0),
            ],
        ];
    }

    private function raise(string $level): string
    {
        return match ($level) {
            'low' => 'moderate',
            'moderate' => 'high',
            'high' => 'critical',
            default => $level,
        };
    }

    private function rank(string $level): int
    {
        return match ($level) {
            'moderate' => 1,
            'high' => 2,
            'critical' => 3,
            default => 0,
        };
    }

    private function num(float|int|string|null $value): string
    {
        return number_format((float) $value, 2);
    }
}
