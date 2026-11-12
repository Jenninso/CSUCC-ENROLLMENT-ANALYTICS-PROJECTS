<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\SystemSetting;

class AlertService
{
    public function __construct(
        private AnalyticsService $analytics,
        private StudentRiskAnalysis $studentRisk,
    ) {}

    public function evaluate(): array
    {
        $data = $this->analytics->dashboard();
        $created = [];

        $declineThreshold = (float) SystemSetting::getValue('decline_threshold_pct', -10);
        $growthThreshold = (float) SystemSetting::getValue('growth_threshold_pct', 15);
        $retentionTarget = (float) SystemSetting::getValue('retention_target', 85);
        $capacity = (int) SystemSetting::getValue('institution_capacity', 5500);

        if ($data['growthRate'] <= $declineThreshold) {
            $created[] = $this->make([
                'title' => 'Enrollment decline beyond threshold',
                'metric' => 'growth_rate',
                'current_value' => $data['growthRate'],
                'expected_value' => $declineThreshold,
                'severity' => $data['growthRate'] <= -15 ? 'critical' : 'high',
                'explanation' => "Institutional growth is {$data['growthRate']}%, below the configured decline threshold of {$declineThreshold}%.",
                'recommended_action' => 'Inspect program rankings, first-year intake, and data quality before changing recruitment budgets.',
            ]);
        }

        if ($data['growthRate'] >= $growthThreshold) {
            $created[] = $this->make([
                'title' => 'Rapid enrollment growth',
                'metric' => 'growth_rate',
                'current_value' => $data['growthRate'],
                'expected_value' => $growthThreshold,
                'severity' => 'medium',
                'explanation' => "Growth of {$data['growthRate']}% exceeds the configured rapid-growth threshold of {$growthThreshold}%.",
                'recommended_action' => 'Review classroom, faculty, and student-service capacity for the next intake.',
            ]);
        }

        if ($data['retentionRate'] > 0 && $data['retentionRate'] < $retentionTarget) {
            $created[] = $this->make([
                'title' => 'Retention below target',
                'metric' => 'retention_rate',
                'current_value' => $data['retentionRate'],
                'expected_value' => $retentionTarget,
                'severity' => 'high',
                'explanation' => "Retention is {$data['retentionRate']}% versus a target of {$retentionTarget}%.",
                'recommended_action' => 'Review withdrawn/dropped counts and year-level continuation. Confirm student-type coding.',
            ]);
        }

        if ($data['totalEnrollment'] > $capacity) {
            $created[] = $this->make([
                'title' => 'Enrollment exceeds planning capacity',
                'metric' => 'total_enrollment',
                'current_value' => $data['totalEnrollment'],
                'expected_value' => $capacity,
                'severity' => 'critical',
                'explanation' => "Current enrollment ({$data['totalEnrollment']}) is above the planning capacity of {$capacity}.",
                'recommended_action' => 'Validate capacity settings and review sectioning constraints.',
            ]);
        }

        foreach ($data['decliningPrograms'] as $program) {
            if ($program['growth_rate'] <= $declineThreshold) {
                $created[] = $this->make([
                    'title' => "Decline in {$program['program_code']}",
                    'program_id' => $program['id'],
                    'metric' => 'program_growth_rate',
                    'current_value' => $program['growth_rate'],
                    'expected_value' => $declineThreshold,
                    'severity' => $program['growth_rate'] <= -20 ? 'critical' : 'high',
                    'explanation' => "{$program['program_name']} changed from {$program['previous']} to {$program['current']} ({$program['growth_rate']}%).",
                    'recommended_action' => 'Validate records, then review recruitment and retention for this program only.',
                ]);
            }
        }

        $this->studentRisk->raiseWarnings();

        return $created;
    }

    private function make(array $payload): Alert
    {
        return Alert::create($payload + ['is_read' => false]);
    }
}
