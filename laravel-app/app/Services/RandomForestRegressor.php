<?php

namespace App\Services;

class RandomForestRegressor
{
    /** @var list<array<string, mixed>> */
    private array $trees = [];

    public function __construct(
        private int $treeCount = 35,
        private int $maxDepth = 7,
        private int $minLeaf = 5,
        private int $seed = 2026,
    ) {}

    /**
     * @param  list<list<float>>  $samples
     * @param  list<float>  $targets
     */
    public function fit(array $samples, array $targets): void
    {
        $count = count($samples);
        if ($count === 0 || $count !== count($targets)) {
            $this->trees = [];

            return;
        }

        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937($this->seed));
        $featureTotal = count($samples[0]);
        $featureCount = max(1, (int) ceil(sqrt($featureTotal)));
        $this->trees = [];

        for ($tree = 0; $tree < $this->treeCount; $tree++) {
            $indices = [];
            for ($row = 0; $row < $count; $row++) {
                $indices[] = $rng->getInt(0, $count - 1);
            }
            $this->trees[] = $this->split($samples, $targets, $indices, 0, $rng, $featureCount, $featureTotal);
        }
    }

    /**
     * @param  list<float>  $features
     */
    public function predict(array $features): float
    {
        if ($this->trees === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($this->trees as $tree) {
            $sum += $this->walk($tree, $features);
        }

        return $sum / count($this->trees);
    }

    /**
     * @param  list<list<float>>  $samples
     * @param  list<float>  $targets
     * @param  list<int>  $indices
     * @return array<string, mixed>
     */
    private function split(array $samples, array $targets, array $indices, int $depth, \Random\Randomizer $rng, int $featureCount, int $featureTotal): array
    {
        $count = count($indices);
        $sum = 0.0;
        $sumSq = 0.0;
        foreach ($indices as $index) {
            $value = $targets[$index];
            $sum += $value;
            $sumSq += $value * $value;
        }
        $mean = $count > 0 ? $sum / $count : 0.0;
        $variance = $count > 0 ? $sumSq - ($sum ** 2) / $count : 0.0;

        if ($depth >= $this->maxDepth || $count < ($this->minLeaf * 2) || $variance < 1e-6) {
            return ['leaf' => $mean];
        }

        $features = range(0, $featureTotal - 1);
        for ($i = $featureTotal - 1; $i > 0; $i--) {
            $swap = $rng->getInt(0, $i);
            [$features[$i], $features[$swap]] = [$features[$swap], $features[$i]];
        }

        $bestScore = INF;
        $bestFeature = null;
        $bestThreshold = null;

        foreach (array_slice($features, 0, $featureCount) as $feature) {
            $min = INF;
            $max = -INF;
            foreach ($indices as $index) {
                $value = $samples[$index][$feature];
                $min = min($min, $value);
                $max = max($max, $value);
            }
            if (($max - $min) < 1e-9) {
                continue;
            }

            for ($step = 1; $step < 8; $step++) {
                $threshold = $min + ($max - $min) * ($step / 8);
                $leftSum = 0.0;
                $leftSq = 0.0;
                $leftCount = 0;
                $rightSum = 0.0;
                $rightSq = 0.0;
                $rightCount = 0;

                foreach ($indices as $index) {
                    $target = $targets[$index];
                    if ($samples[$index][$feature] <= $threshold) {
                        $leftSum += $target;
                        $leftSq += $target * $target;
                        $leftCount++;
                    } else {
                        $rightSum += $target;
                        $rightSq += $target * $target;
                        $rightCount++;
                    }
                }

                if ($leftCount < $this->minLeaf || $rightCount < $this->minLeaf) {
                    continue;
                }

                $score = ($leftSq - ($leftSum ** 2) / $leftCount) + ($rightSq - ($rightSum ** 2) / $rightCount);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestFeature = $feature;
                    $bestThreshold = $threshold;
                }
            }
        }

        if ($bestFeature === null) {
            return ['leaf' => $mean];
        }

        $left = [];
        $right = [];
        foreach ($indices as $index) {
            if ($samples[$index][$bestFeature] <= $bestThreshold) {
                $left[] = $index;
            } else {
                $right[] = $index;
            }
        }

        return [
            'feature' => $bestFeature,
            'threshold' => $bestThreshold,
            'left' => $this->split($samples, $targets, $left, $depth + 1, $rng, $featureCount, $featureTotal),
            'right' => $this->split($samples, $targets, $right, $depth + 1, $rng, $featureCount, $featureTotal),
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<float>  $features
     */
    private function walk(array $node, array $features): float
    {
        if (array_key_exists('leaf', $node)) {
            return (float) $node['leaf'];
        }

        $feature = (int) $node['feature'];
        $value = $features[$feature] ?? 0.0;

        return $value <= $node['threshold']
            ? $this->walk($node['left'], $features)
            : $this->walk($node['right'], $features);
    }
}
