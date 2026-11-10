<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Forecast;
use App\Models\Program;

class ForecastService
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * Compare baseline and statistical models, persist the best overall forecast.
     */
    public function generate(?int $programId = null, ?int $collegeId = null, int $horizon = 1): array
    {
        $series = $this->analytics->seriesByProgram($programId, $collegeId);
        $values = array_values($series['values']);
        $labels = $series['labels'];

        if (count($values) < 2) {
            return [
                'ok' => false,
                'message' => 'At least two historical periods are required to generate a forecast.',
                'series' => $series,
                'models' => [],
            ];
        }

        $models = $this->compareModels($values);
        usort($models, fn ($a, $b) => $a['mae'] <=> $b['mae']);
        $best = $models[0];

        $forecasts = [];
        $lastLabel = (string) end($labels);
        $next = $this->nextPeriodLabel($lastLabel);

        $level = $best['point'];
        $std = max($best['residual_std'], 1);
        for ($i = 0; $i < $horizon; $i++) {
            $predicted = (int) max(round($level), 0);
            $lower = (int) max(round($predicted - 1.96 * $std), 0);
            $upper = (int) round($predicted + 1.96 * $std);
            $period = $i === 0 ? $next : $this->nextPeriodLabel($period ?? $next);
            $forecasts[] = [
                'period' => $period,
                'predicted' => $predicted,
                'lower' => $lower,
                'upper' => $upper,
            ];
            $level = $predicted;
        }

        $saved = Forecast::create([
            'program_id' => $programId,
            'college_id' => $collegeId,
            'forecast_period' => $forecasts[0]['period'],
            'predicted_enrollment' => $forecasts[0]['predicted'],
            'lower_bound' => $forecasts[0]['lower'],
            'upper_bound' => $forecasts[0]['upper'],
            'model_used' => $best['name'],
            'accuracy_metric' => $best['mae'],
        ]);

        return [
            'ok' => true,
            'series' => $series,
            'models' => $models,
            'best' => $best,
            'forecasts' => $forecasts,
            'saved' => $saved,
            'scope' => $this->scopeLabel($programId, $collegeId),
        ];
    }

    public function compareModels(array $values): array
    {
        $n = count($values);
        $holdout = $n >= 4 ? 1 : 0;
        $train = $holdout ? array_slice($values, 0, -$holdout) : $values;
        $test = $holdout ? array_slice($values, -$holdout) : [];

        $candidates = [
            $this->evaluate('Naive', $train, $test, $values, fn ($hist) => $this->naive($hist)),
            $this->evaluate('Moving Average (3)', $train, $test, $values, fn ($hist) => $this->movingAverage($hist, 3)),
            $this->evaluate('Linear Regression', $train, $test, $values, fn ($hist) => $this->linear($hist)),
            $this->evaluate('Exponential Smoothing', $train, $test, $values, fn ($hist) => $this->exponentialSmoothing($hist, 0.4)),
        ];

        return $candidates;
    }

    private function evaluate(string $name, array $train, array $test, array $full, callable $fn): array
    {
        $point = $fn($full);
        $residualStd = $this->residualStd($full, $fn);

        $mae = $rmse = $mape = 0.0;
        if ($test) {
            $pred = $fn($train);
            $actual = $test[0];
            $err = abs($pred - $actual);
            $mae = round($err, 2);
            $rmse = round($err, 2);
            $mape = $actual != 0 ? round(($err / abs($actual)) * 100, 2) : 0;
        } else {
            $errors = [];
            for ($i = 1; $i < count($full); $i++) {
                $pred = $fn(array_slice($full, 0, $i));
                $errors[] = abs($pred - $full[$i]);
            }
            $mae = $errors ? round(array_sum($errors) / count($errors), 2) : 0;
            $rmse = $errors ? round(sqrt(array_sum(array_map(fn ($e) => $e ** 2, $errors)) / count($errors)), 2) : 0;
            $mape = 0;
        }

        return [
            'name' => $name,
            'point' => $point,
            'mae' => $mae,
            'rmse' => $rmse,
            'mape' => $mape,
            'residual_std' => $residualStd,
        ];
    }

    private function naive(array $hist): float
    {
        return (float) ($hist[array_key_last($hist)] ?? 0);
    }

    private function movingAverage(array $hist, int $window): float
    {
        $slice = array_slice($hist, -$window);

        return $slice ? array_sum($slice) / count($slice) : 0.0;
    }

    private function linear(array $hist): float
    {
        $n = count($hist);
        if ($n < 2) {
            return $this->naive($hist);
        }
        $sumX = $sumY = $sumXY = $sumX2 = 0.0;
        foreach ($hist as $i => $y) {
            $sumX += $i;
            $sumY += $y;
            $sumXY += $i * $y;
            $sumX2 += $i * $i;
        }
        $den = ($n * $sumX2) - ($sumX ** 2);
        $slope = $den != 0 ? (($n * $sumXY) - ($sumX * $sumY)) / $den : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return $intercept + $slope * $n;
    }

    private function exponentialSmoothing(array $hist, float $alpha): float
    {
        $s = (float) $hist[0];
        foreach ($hist as $y) {
            $s = $alpha * $y + (1 - $alpha) * $s;
        }

        return $s;
    }

    private function residualStd(array $hist, callable $fn): float
    {
        if (count($hist) < 3) {
            return 0.0;
        }
        $residuals = [];
        for ($i = 2; $i < count($hist); $i++) {
            $pred = $fn(array_slice($hist, 0, $i));
            $residuals[] = $hist[$i] - $pred;
        }
        $mean = array_sum($residuals) / count($residuals);
        $var = array_sum(array_map(fn ($r) => ($r - $mean) ** 2, $residuals)) / count($residuals);

        return sqrt($var);
    }

    private function nextPeriodLabel(string $label): string
    {
        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $label, $m)) {
            return ((int) $m[1] + 1).'-'.((int) $m[2] + 1);
        }

        return 'Next period';
    }

    private function scopeLabel(?int $programId, ?int $collegeId): string
    {
        if ($programId) {
            return Program::find($programId)?->program_name ?? 'Program';
        }
        if ($collegeId) {
            return College::find($collegeId)?->college_name ?? 'College';
        }

        return 'Institution';
    }

    public function latestOverall(): ?Forecast
    {
        return Forecast::query()->whereNull('program_id')->whereNull('college_id')->latest()->first();
    }
}
