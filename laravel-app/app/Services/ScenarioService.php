<?php

namespace App\Services;

class ScenarioService
{
    /**
     * Scenario outputs are simulations, not statistical forecasts.
     */
    public function run(int $currentTotal, int $newStudents, int $returningStudents, float $recruitmentPct, float $retentionPct, int $capacity): array
    {
        $base = [
            'label' => 'Current scenario',
            'total' => $currentTotal,
            'note' => 'Observed latest-period total.',
        ];

        $recruitmentDelta = (int) round($newStudents * ($recruitmentPct / 100));
        $recruitmentTotal = $currentTotal + $recruitmentDelta;
        $recruitment = [
            'label' => 'Improved recruitment (simulation)',
            'total' => $recruitmentTotal,
            'note' => "Applies {$recruitmentPct}% to new-student headcount only (+{$recruitmentDelta}).",
        ];

        $retentionDelta = (int) round($returningStudents * ($retentionPct / 100));
        $retentionTotal = $currentTotal + $retentionDelta;
        $retention = [
            'label' => 'Improved retention (simulation)',
            'total' => $retentionTotal,
            'note' => "Applies {$retentionPct}% to returning/continuing headcount only (+{$retentionDelta}).",
        ];

        $combined = $currentTotal + $recruitmentDelta + $retentionDelta;
        $combinedCapped = min($combined, $capacity);

        return [
            'disclaimer' => 'These figures are simulations based on user-entered assumptions. They are not model forecasts and should not be treated as predicted enrollment.',
            'current' => $base,
            'recruitment' => $recruitment,
            'retention' => $retention,
            'combined' => [
                'label' => 'Combined simulation',
                'total' => $combined,
                'capped' => $combinedCapped,
                'exceeds_capacity' => $combined > $capacity,
                'capacity' => $capacity,
            ],
        ];
    }
}
