<?php

namespace Tests\Unit;

use App\Services\ForecastService;
use App\Services\StatisticalTrainingData;
use Tests\TestCase;

class RandomForestForecastTest extends TestCase
{
    public function test_statistical_training_reaches_the_random_forest_sample_goal(): void
    {
        $data = (new StatisticalTrainingData)->build([420, 460, 510, 560, 640, 700, 760]);

        $this->assertGreaterThanOrEqual(StatisticalTrainingData::GOAL, $data['rows']);
        $this->assertSame(7, $data['real_periods']);
        $this->assertGreaterThan(0, $data['series']);
    }

    public function test_random_forest_is_the_main_forecast_and_continues_the_trend(): void
    {
        $result = app(ForecastService::class)->forecastValues([420, 460, 510, 560, 640, 700, 760], 2);

        $this->assertSame('Random Forest', $result['best']['name']);
        $this->assertSame('Random Forest', $result['models'][0]['name']);
        $this->assertGreaterThanOrEqual(StatisticalTrainingData::GOAL, $result['training']['rows']);
        $this->assertGreaterThan(760, $result['points'][0]);
        $this->assertLessThan(980, $result['points'][0]);
        $this->assertGreaterThan($result['points'][0], $result['points'][1]);
    }
}
