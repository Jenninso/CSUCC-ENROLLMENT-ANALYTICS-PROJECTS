<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\SystemSetting;

class InsightService
{
    public function __construct(
        private AnalyticsService $analytics,
        private ForecastService $forecasts,
    ) {}

    public function generate(): array
    {
        $data = $this->analytics->dashboard();
        $insights = [];

        $growth = $data['growthRate'];
        $total = $data['totalEnrollment'];
        $previous = $data['previousEnrollment'];
        $year = $data['latestAY']?->academic_year ?? 'current period';

        $insights[] = $this->store([
            'insight_type' => $growth >= 0 ? 'trend' : 'decline_alert',
            'title' => $growth >= 0
                ? "Institutional enrollment is {$growth}% above the previous academic year"
                : "Institutional enrollment declined by ".abs($growth)."% versus the previous academic year",
            'description' => "Total enrollment is {$total} in {$year}, compared with {$previous} in the prior year.",
            'situation' => $growth >= 0
                ? "Overall headcount increased relative to the previous academic year."
                : "Overall headcount decreased relative to the previous academic year.",
            'evidence' => "FACT: Current enrollment = {$total}. Previous enrollment = {$previous}. Growth rate = {$growth}%.",
            'interpretation' => $growth < -5
                ? "The decline is large enough to warrant review of recruitment, retention, and program mix. This is an interpretation, not proof of a single cause."
                : "The change may reflect normal year-to-year variation, program demand shifts, or intake policy. Administrators should inspect program-level contributions before acting.",
            'forecast_note' => null,
            'recommendation' => $growth < 0
                ? "Review program-level growth, first-year intake, and retention before changing resource allocations. Do not treat this as an automatic directive to close or expand any program."
                : "Confirm whether capacity, faculty load, and student services can support the current trajectory.",
            'severity' => $this->severityFromGrowth($growth),
            'supporting_metric' => json_encode(['current' => $total, 'previous' => $previous, 'growth' => $growth]),
            'confidence' => $previous > 0 ? 85 : 40,
        ]);

        foreach ($data['decliningPrograms']->take(5) as $program) {
            $insights[] = $this->store([
                'program_id' => $program['id'],
                'insight_type' => 'decline_alert',
                'title' => "{$program['program_code']} declined {$program['growth_rate']}%",
                'description' => "{$program['program_name']} enrollment moved from {$program['previous']} to {$program['current']}.",
                'situation' => "Enrollment in {$program['program_name']} is lower than the previous academic year.",
                'evidence' => "FACT: {$program['previous']} → {$program['current']} ({$program['growth_rate']}%).",
                'interpretation' => "Possible explanations include weaker demand, intake caps, scheduling, retention, or competition. These are hypotheses, not confirmed causes.",
                'recommendation' => "Administrators may review recruitment performance, retention, scheduling constraints, and program demand indicators before allocating additional recruitment resources.",
                'severity' => $program['growth_rate'] <= -15 ? 'high' : 'medium',
                'supporting_metric' => json_encode($program),
                'confidence' => 78,
            ]);
        }

        foreach ($data['growingPrograms']->take(3) as $program) {
            $insights[] = $this->store([
                'program_id' => $program['id'],
                'insight_type' => 'growth_opportunity',
                'title' => "{$program['program_code']} shows sustained relative growth ({$program['growth_rate']}%)",
                'description' => "{$program['program_name']} grew from {$program['previous']} to {$program['current']} students.",
                'situation' => "This program is contributing more students than in the previous period.",
                'evidence' => "FACT: {$program['previous']} → {$program['current']} ({$program['growth_rate']}%).",
                'interpretation' => "Growth may indicate stronger demand. Capacity, faculty loading, and classroom availability should be checked before treating growth as unconstrained.",
                'recommendation' => "Consider a capacity assessment (sections, faculty, laboratories) rather than assuming additional marketing is required.",
                'severity' => 'medium',
                'supporting_metric' => json_encode($program),
                'confidence' => 75,
            ]);
        }

        $retention = $data['retentionRate'];
        $target = (float) SystemSetting::getValue('retention_target', 85);
        if ($retention > 0 && $retention < $target) {
            $insights[] = $this->store([
                'insight_type' => 'retention_alert',
                'title' => "Retention is {$retention}%, below the planning target of {$target}%",
                'description' => "Retention is defined as returning and continuing students divided by previous-period total enrollment.",
                'situation' => "Measured retention is below the configured institutional target.",
                'evidence' => "FACT: Retention rate = {$retention}%. Target = {$target}%. Previous enrollment = {$previous}. Retained = {$data['returningStudents']}.",
                'interpretation' => "Low retention can reflect dropout, transfer-out, graduation timing, or data classification of student types. Validate definitions before acting.",
                'recommendation' => "Review year-level continuation, withdrawn/dropped counts, and advising load. Avoid concluding that a program must be closed.",
                'severity' => $retention < ($target - 10) ? 'high' : 'medium',
                'supporting_metric' => json_encode(['retention' => $retention, 'target' => $target]),
                'confidence' => 70,
            ]);
        }

        $forecast = $this->forecasts->generate();
        if ($forecast['ok']) {
            $pred = $forecast['forecasts'][0];
            $capacity = (int) SystemSetting::getValue('institution_capacity', 5500);
            $insights[] = $this->store([
                'insight_type' => 'forecast',
                'title' => "Next-period enrollment is estimated at {$pred['predicted']} ({$pred['lower']}–{$pred['upper']})",
                'description' => "Best model: {$forecast['best']['name']} (MAE {$forecast['best']['mae']}). This is a model-generated estimate, not an observed count.",
                'situation' => "A statistical forecast was produced from historical institutional totals.",
                'evidence' => "FORECAST: {$pred['predicted']} students for {$pred['period']} using {$forecast['best']['name']}. MAE = {$forecast['best']['mae']}.",
                'interpretation' => "Intervals reflect residual variation from the selected model. Accuracy depends on data completeness and whether future conditions resemble the past.",
                'forecast_note' => "{$pred['period']}: {$pred['predicted']} (interval {$pred['lower']}–{$pred['upper']}).",
                'recommendation' => $pred['predicted'] > $capacity
                    ? "Projected enrollment may exceed the planning capacity of {$capacity}. Review sectioning and facilities before the next intake."
                    : "Use this estimate for planning, then update it when new enrollment files are imported.",
                'severity' => $pred['predicted'] > $capacity ? 'high' : 'low',
                'supporting_metric' => json_encode($forecast['best']),
                'confidence' => 65,
            ]);
        }

        $this->flagAnomalies($data, $insights);

        return $insights;
    }

    private function flagAnomalies(array $data, array &$insights): void
    {
        foreach ($data['topPrograms'] as $program) {
            $series = $this->analytics->seriesByProgram($program['id']);
            $values = $series['values'];
            if (count($values) < 4) {
                continue;
            }
            $hist = array_slice($values, 0, -1);
            $last = end($values);
            $mean = array_sum($hist) / count($hist);
            $std = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $hist)) / count($hist));
            if ($std <= 0) {
                continue;
            }
            $z = abs(($last - $mean) / $std);
            if ($z >= 2) {
                $insights[] = $this->store([
                    'program_id' => $program['id'],
                    'insight_type' => 'anomaly_alert',
                    'title' => "{$program['program_code']} is unusually different from its historical pattern",
                    'description' => "Latest enrollment ({$last}) is approximately ".round($z, 1)." standard deviations from the historical mean ({$mean}).",
                    'situation' => "This observation is statistically unusual compared with the program's own history.",
                    'evidence' => "FACT: Latest = {$last}. Historical mean = ".round($mean, 1).". z-score = ".round($z, 2).".",
                    'interpretation' => "Anomalies can be real demand shocks or data-quality issues (duplicate import, missing semester, mis-coded program).",
                    'recommendation' => "Validate the latest records for this program, then review intake and retention if the data are confirmed.",
                    'severity' => $z >= 3 ? 'critical' : 'high',
                    'supporting_metric' => json_encode(['z' => $z, 'last' => $last, 'mean' => $mean]),
                    'confidence' => 60,
                ]);
            }
        }
    }

    private function store(array $payload): AiInsight
    {
        $payload['status'] = $payload['status'] ?? 'pending_review';

        return AiInsight::create($payload);
    }

    private function severityFromGrowth(float $growth): string
    {
        if ($growth <= -15) {
            return 'critical';
        }
        if ($growth <= -7) {
            return 'high';
        }
        if ($growth < 0) {
            return 'medium';
        }

        return 'low';
    }
}
