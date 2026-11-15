<?php

namespace App\Services;

class StatisticalTrainingData
{
    public const GOAL = 480;

    /**
     * Build supervised rows from the real series plus simulated series
     * that keep that history and extend it by the typical year-to-year change.
     *
     * @param  list<int|float>  $values
     * @return array{
     *     samples: list<list<float>>,
     *     targets: list<float>,
     *     rows: int,
     *     series: int,
     *     real_rows: int,
     *     real_periods: int,
     *     goal: int,
     *     observed_residual: float
     * }
     */
    public function build(array $values, int $seed = 20261115): array
    {
        $periods = count($values);
        $deltas = [];
        for ($index = 1; $index < $periods; $index++) {
            $deltas[] = (float) $values[$index] - (float) $values[$index - 1];
        }
        $medianDelta = $this->median($deltas);
        $deviations = array_map(fn (float $delta) => abs($delta - $medianDelta), $deltas);
        $scale = max($this->median($deviations), abs((float) $values[$periods - 1]) * 0.01, 1.0);
        $observedResidual = $this->standardDeviation($deltas);
        $windowsPerSeries = max(1, ($periods - 1) + 3);
        $seriesCount = (int) max(40, ceil(self::GOAL / $windowsPerSeries));
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937($seed));

        $samples = [];
        $targets = [];
        $realWindows = $this->windows($values);

        foreach ($realWindows as [$features, $target]) {
            for ($repeat = 0; $repeat < 6; $repeat++) {
                $samples[] = $features;
                $targets[] = $target;
            }
        }

        for ($series = 0; $series < $seriesCount; $series++) {
            $simulated = array_map('floatval', $values);
            $level = (float) $simulated[$periods - 1];

            for ($step = 0; $step < 3; $step++) {
                $typical = $rng->nextFloat() < 0.75;
                $delta = $typical
                    ? $medianDelta + ($this->gaussian($rng) * $scale)
                    : $deltas[$rng->getInt(0, count($deltas) - 1)];
                $level = max(0.0, $level + $delta);
                $simulated[] = $level;
            }

            foreach ($this->windows($simulated) as [$features, $target]) {
                $samples[] = $features;
                $targets[] = $target;
            }
        }

        return [
            'samples' => $samples,
            'targets' => $targets,
            'rows' => count($targets),
            'series' => $seriesCount,
            'real_rows' => count($realWindows),
            'real_periods' => $periods,
            'goal' => self::GOAL,
            'observed_residual' => round($observedResidual, 2),
        ];
    }

    /**
     * Feature row used to predict the next value after $history.
     *
     * @param  list<int|float>  $history
     * @return list<float>
     */
    public function nextFeatures(array $history): array
    {
        $index = count($history);
        $lag1 = (float) $history[$index - 1];
        $lag2 = $index >= 2 ? (float) $history[$index - 2] : $lag1;
        $lag3 = $index >= 3 ? (float) $history[$index - 3] : $lag2;

        return [
            $lag1,
            $lag2,
            $lag3,
            $lag1 - $lag2,
            ($lag1 + $lag2 + $lag3) / 3,
            (float) $index,
        ];
    }

    /**
     * @param  list<int|float>  $series
     * @return list{array{0: list<float>, 1: float}}
     */
    private function windows(array $series): array
    {
        $rows = [];
        $count = count($series);
        for ($index = 1; $index < $count; $index++) {
            $rows[] = [$this->nextFeatures(array_slice($series, 0, $index)), (float) $series[$index]];
        }

        return $rows;
    }

    /**
     * @param  list<float>  $values
     */
    private function median(array $values): float
    {
        $sorted = array_values($values);
        sort($sorted);
        $count = count($sorted);
        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $sorted[$middle];
        }

        return ($sorted[$middle - 1] + $sorted[$middle]) / 2;
    }

    /**
     * @param  list<float>  $values
     */
    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sum = 0.0;
        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return sqrt($sum / ($count - 1));
    }

    private function gaussian(\Random\Randomizer $rng): float
    {
        $u1 = max($rng->nextFloat(), 1e-9);
        $u2 = $rng->nextFloat();

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }
}
