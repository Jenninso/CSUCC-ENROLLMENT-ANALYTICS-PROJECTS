<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StudentAttritionService
{
    /**
     * Predict which currently enrolled students resemble those who already dropped, withdrew, or stopped.
     * Status is the answer being learned. It is not one of the inputs.
     *
     * @return array<string, mixed>
     */
    public function predict(): array
    {
        $rows = DB::table('student_records')
            ->join('programs', 'programs.id', '=', 'student_records.program_id')
            ->get([
                'student_records.student_id',
                'student_records.year_level',
                'student_records.age',
                'student_records.gpa',
                'student_records.attendance_percent',
                'student_records.failed_subjects',
                'student_records.subjects_count',
                'student_records.units_enrolled',
                'student_records.gender',
                'student_records.scholarship_status',
                'student_records.enrolled_status',
                'student_records.student_type',
                'programs.program_code',
            ])
            ->all();

        return $this->fromRows($rows);
    }

    /**
     * @param  list<object>  $rows
     * @return array<string, mixed>
     */
    public function fromRows(array $rows): array
    {
        $labeled = [];
        foreach ($rows as $row) {
            $labeled[] = [
                'row' => $row,
                'features' => $this->features($row),
                'attrited' => $this->attrited($row) ? 1.0 : 0.0,
            ];
        }

        $positives = array_values(array_filter($labeled, fn (array $item) => $item['attrited'] === 1.0));
        $negatives = array_values(array_filter($labeled, fn (array $item) => $item['attrited'] === 0.0));

        if (count($positives) < 30 || count($negatives) < 30) {
            return [
                'ok' => false,
                'message' => 'At least 30 students who left and 30 who stayed are needed before Random Forest can estimate attrition.',
            ];
        }

        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(20261115));
        $sample = array_merge(
            $this->take($positives, min(900, count($positives)), $rng),
            $this->take($negatives, min(900, count($negatives)), $rng),
        );
        $sample = $this->shuffle($sample, $rng);
        $split = max(1, (int) floor(count($sample) * 0.8));
        $train = array_slice($sample, 0, $split);
        $test = array_slice($sample, $split);

        $forest = new RandomForestRegressor(treeCount: 21, maxDepth: 8, minLeaf: 8, seed: 20261115);
        $forest->fit($this->matrix($train), $this->targets($train));
        $metrics = $this->score($forest, $test);

        $forest->fit($this->matrix($sample), $this->targets($sample));
        $flagged = [];
        foreach ($negatives as $item) {
            $chance = $forest->predict($item['features']);
            if ($chance < 0.5) {
                continue;
            }
            $row = $item['row'];
            $chancePercent = (int) round($chance * 100);
            $flagged[] = [
                'student_id' => (string) $row->student_id,
                'program' => (string) ($row->program_code ?? ''),
                'year_level' => (int) $row->year_level,
                'gpa' => round((float) $row->gpa, 2),
                'attendance' => round((float) $row->attendance_percent, 1),
                'failed_subjects' => (int) $row->failed_subjects,
                'chance' => $chancePercent,
                'suggestion' => $this->suggestion($row, $chancePercent),
            ];
        }

        usort($flagged, fn (array $a, array $b) => $b['chance'] <=> $a['chance']);

        return [
            'ok' => true,
            'examined' => count($labeled),
            'left' => count($positives),
            'stayed' => count($negatives),
            'trained' => count($sample),
            'accuracy' => $metrics['accuracy'],
            'caught' => $metrics['caught'],
            'flagged' => count($flagged),
            'students' => array_slice($flagged, 0, 15),
        ];
    }

    /**
     * @param  list<array{row: object, features: list<float>, attrited: float}>  $items
     * @return list<array{row: object, features: list<float>, attrited: float}>
     */
    private function take(array $items, int $limit, \Random\Randomizer $rng): array
    {
        $items = $this->shuffle($items, $rng);

        return array_slice($items, 0, $limit);
    }

    /**
     * @param  list<array{row: object, features: list<float>, attrited: float}>  $items
     * @return list<array{row: object, features: list<float>, attrited: float}>
     */
    private function shuffle(array $items, \Random\Randomizer $rng): array
    {
        for ($i = count($items) - 1; $i > 0; $i--) {
            $swap = $rng->getInt(0, $i);
            [$items[$i], $items[$swap]] = [$items[$swap], $items[$i]];
        }

        return $items;
    }

    /**
     * @param  list<array{features: list<float>}>  $items
     * @return list<list<float>>
     */
    private function matrix(array $items): array
    {
        return array_map(fn (array $item) => $item['features'], $items);
    }

    /**
     * @param  list<array{attrited: float}>  $items
     * @return list<float>
     */
    private function targets(array $items): array
    {
        return array_map(fn (array $item) => $item['attrited'], $items);
    }

    /**
     * @param  list<array{features: list<float>, attrited: float}>  $test
     * @return array{accuracy: float, caught: float}
     */
    private function score(RandomForestRegressor $forest, array $test): array
    {
        if ($test === []) {
            return ['accuracy' => 0.0, 'caught' => 0.0];
        }

        $correct = 0;
        $actualLeft = 0;
        $caught = 0;
        foreach ($test as $item) {
            $predicted = $forest->predict($item['features']) >= 0.5 ? 1.0 : 0.0;
            if ($predicted === $item['attrited']) {
                $correct++;
            }
            if ($item['attrited'] === 1.0) {
                $actualLeft++;
                if ($predicted === 1.0) {
                    $caught++;
                }
            }
        }

        return [
            'accuracy' => round(($correct / count($test)) * 100, 1),
            'caught' => $actualLeft > 0 ? round(($caught / $actualLeft) * 100, 1) : 0.0,
        ];
    }

    private function features(object $row): array
    {
        return [
            (float) $row->year_level,
            (float) $row->age,
            (float) $row->gpa,
            (float) $row->attendance_percent,
            (float) $row->failed_subjects,
            (float) $row->subjects_count,
            (float) $row->units_enrolled,
            ($row->gender ?? '') === 'female' ? 1.0 : 0.0,
            ($row->scholarship_status ?? 'none') !== 'none' ? 1.0 : 0.0,
        ];
    }

    private function suggestion(object $row, int $chance): string
    {
        $gpa = number_format((float) $row->gpa, 2);
        $failed = (int) $row->failed_subjects;
        $attendance = number_format((float) $row->attendance_percent, 0);
        $year = (int) $row->year_level;
        $help = [];

        if ($failed > 0 || (float) $row->gpa >= 2.25) {
            $failedPart = $failed > 0
                ? 'the '.$failed.' failed '.($failed === 1 ? 'subject' : 'subjects').' behind '
                : '';
            $help[] = 'Arrange tutoring for '.$failedPart.'the '.$gpa.' GPA';
        }
        if ((float) $row->attendance_percent < 85) {
            $help[] = 'follow up on absences while attendance is '.$attendance.'%';
        }
        if ($year === 1) {
            $help[] = 'assign a first-year adviser';
        }
        if ($chance >= 75) {
            $help[] = 'schedule guidance counseling this week for the '.$chance.'% estimate';
        }
        if (($row->student_type ?? '') === 'new') {
            $help[] = 'include this new student in orientation follow-up';
        }
        if ($help === []) {
            $help[] = 'Check in with this year '.$year.' student and review the next grading period';
        }

        $text = ucfirst(implode('; ', $help));

        return str_ends_with($text, '.') ? $text : $text.'.';
    }

    private function attrited(object $row): bool
    {
        return in_array($row->enrolled_status, ['dropped', 'withdrawn'], true)
            || in_array($row->student_type, ['withdraw_stop', 'shiftee'], true);
    }
}
