<?php

namespace App\Services;

use App\Models\College;
use App\Models\Forecast;
use App\Models\Program;

class ForecastService
{
    public function __construct(
        private AnalyticsService $analytics,
        private StatisticalTrainingData $training,
    ) {}

    /**
     * Train Random Forest on statistical samples drawn from the observed series and persist the forecast.
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

        $result = $this->forecastValues($values, $horizon);
        $forecasts = [];
        $lastLabel = (string) end($labels);
        $next = $this->nextPeriodLabel($lastLabel);
        $std = max($result['band'], 1);

        foreach ($result['points'] as $index => $level) {
            $predicted = (int) max(round($level), 0);
            $period = $index === 0 ? $next : $this->nextPeriodLabel($period ?? $next);
            $forecasts[] = [
                'period' => $period,
                'predicted' => $predicted,
                'lower' => (int) max(round($predicted - 1.96 * $std), 0),
                'upper' => (int) round($predicted + 1.96 * $std),
            ];
        }

        $saved = Forecast::create([
            'program_id' => $programId,
            'college_id' => $collegeId,
            'forecast_period' => $forecasts[0]['period'],
            'predicted_enrollment' => $forecasts[0]['predicted'],
            'lower_bound' => $forecasts[0]['lower'],
            'upper_bound' => $forecasts[0]['upper'],
            'model_used' => 'Random Forest',
            'accuracy_metric' => $result['best']['mae'],
        ]);

        return [
            'ok' => true,
            'series' => $series,
            'models' => $result['models'],
            'best' => $result['best'],
            'training' => $result['training'],
            'forecasts' => $forecasts,
            'saved' => $saved,
            'scope' => $this->scopeLabel($programId, $collegeId),
        ];
    }

    /**
     * @param  list<int|float>  $values
     * @return array{
     *     models: list<array{name: string, point: float, mae: float, rmse: float, mape: float, residual_std: float}>,
     *     best: array{name: string, point: float, mae: float, rmse: float, mape: float, residual_std: float},
     *     points: list<float>,
     *     training: array<string, int|float>,
     *     band: float
     * }
     */
    public function forecastValues(array $values, int $horizon = 1): array
    {
        $holdout = count($values) >= 3;
        $evaluationValues = $holdout ? array_slice($values, 0, -1) : $values;
        $evaluation = $this->fit($evaluationValues, 20261115);
        $evaluationPoint = $this->project($evaluation['forest'], $evaluationValues, 1)[0];

        if ($holdout) {
            $actual = (float) $values[array_key_last($values)];
            $error = abs($evaluationPoint - $actual);
            $mae = round($error, 2);
            $rmse = $mae;
            $mape = $actual != 0.0 ? round(($error / abs($actual)) * 100, 2) : 0.0;
        } else {
            $mae = 0.0;
            $rmse = 0.0;
            $mape = 0.0;
            $error = 0.0;
        }

        $fitted = $this->fit($values, 20261116);
        $points = $this->project($fitted['forest'], $values, $horizon);
        $observedResidual = (float) $fitted['training']['observed_residual'];
        $last = (float) $values[array_key_last($values)];
        $band = max($observedResidual, $error, $last * 0.02, 1);

        $model = [
            'name' => 'Random Forest',
            'point' => $points[0],
            'mae' => $mae,
            'rmse' => $rmse,
            'mape' => $mape,
            'residual_std' => round($band, 2),
        ];

        return [
            'models' => [$model],
            'best' => $model,
            'points' => $points,
            'training' => [
                'rows' => $fitted['training']['rows'],
                'series' => $fitted['training']['series'],
                'real_rows' => $fitted['training']['real_rows'],
                'real_periods' => $fitted['training']['real_periods'],
                'goal' => $fitted['training']['goal'],
                'observed_residual' => $observedResidual,
            ],
            'band' => $band,
        ];
    }

    /**
     * @param  list<int|float>  $values
     * @return array{forest: RandomForestRegressor, training: array<string, int|float|list<mixed>>}
     */
    private function fit(array $values, int $seed): array
    {
        $data = $this->training->build($values, $seed);
        $forest = new RandomForestRegressor(seed: $seed);
        $forest->fit($data['samples'], $data['targets']);

        return ['forest' => $forest, 'training' => $data];
    }

    /**
     * @param  list<int|float>  $history
     * @return list<float>
     */
    private function project(RandomForestRegressor $forest, array $history, int $horizon): array
    {
        $extended = array_map('floatval', $history);
        $points = [];

        for ($step = 0; $step < $horizon; $step++) {
            $predicted = max(0.0, $forest->predict($this->training->nextFeatures($extended)));
            $extended[] = $predicted;
            $points[] = $predicted;
        }

        return $points;
    }

    private function nextPeriodLabel(string $label): string
    {
        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $label, $matches)) {
            return ((int) $matches[1] + 1).'-'.((int) $matches[2] + 1);
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
