<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction;

use InvalidArgumentException;

/**
 * Independent Poisson baseline. The caller supplies expected goals; this class
 * does not derive them from FryBet's data, odds or private selection rules.
 */
final class PoissonModel
{
    private float $homeMean;
    private float $awayMean;

    public function __construct(float $homeExpectedGoals, float $awayExpectedGoals)
    {
        foreach ([$homeExpectedGoals, $awayExpectedGoals] as $mean) {
            if (!is_finite($mean) || $mean < 0.0 || $mean > 100.0) {
                throw new InvalidArgumentException('Expected goals must be finite and between 0 and 100.');
            }
        }
        $this->homeMean = $homeExpectedGoals;
        $this->awayMean = $awayExpectedGoals;
    }

    public function exactScore(int $homeGoals, int $awayGoals): float
    {
        if ($homeGoals < 0 || $awayGoals < 0 || $homeGoals > 200 || $awayGoals > 200) {
            throw new InvalidArgumentException('Goals must be integers between 0 and 200.');
        }

        return self::probabilities($this->homeMean, $homeGoals)[$homeGoals]
            * self::probabilities($this->awayMean, $awayGoals)[$awayGoals];
    }

    /** Returns one mode. If a mean is a positive integer, a second mode exists. */
    public function mostLikelyScore(): array
    {
        $home = (int) floor($this->homeMean);
        $away = (int) floor($this->awayMean);

        return ['home' => $home, 'away' => $away, 'probability' => $this->exactScore($home, $away)];
    }

    /** Both teams score at least once. */
    public function bothTeamsToScore(): float
    {
        return -expm1(-$this->homeMean) * -expm1(-$this->awayMean);
    }

    /** Both teams' combined total is at least three goals. */
    public function overTwoPointFive(): float
    {
        $mean = $this->homeMean + $this->awayMean;
        if ($mean === 0.0) {
            return 0.0;
        }
        if ($mean < 0.001) {
            // Sum the upper tail directly to avoid cancellation for small means.
            $term = exp(-$mean) * $mean * $mean * $mean / 6.0;
            $sum = $term;
            for ($goals = 4; $goals <= 200; $goals++) {
                $term *= $mean / $goals;
                $sum += $term;
                if ($term <= $sum * 1.0e-15) {
                    break;
                }
            }
            return $sum;
        }

        return max(0.0, min(1.0, 1.0 - exp(-$mean) * (1.0 + $mean + $mean * $mean / 2.0)));
    }

    /**
     * Sum scorelines through maxGoals for each side. The three outcomes are
     * unnormalized lower bounds; omitted is the probability outside the grid.
     *
     * @return array{home: float, draw: float, away: float, covered: float, omitted: float}
     */
    public function outcomes(int $maxGoals = 10): array
    {
        if ($maxGoals < 0 || $maxGoals > 200) {
            throw new InvalidArgumentException('Maximum goals must be between 0 and 200.');
        }
        $home = self::probabilities($this->homeMean, $maxGoals);
        $away = self::probabilities($this->awayMean, $maxGoals);
        $totals = ['home' => 0.0, 'draw' => 0.0, 'away' => 0.0];
        foreach ($home as $h => $homeProbability) {
            foreach ($away as $a => $awayProbability) {
                $outcome = $h > $a ? 'home' : ($h < $a ? 'away' : 'draw');
                $totals[$outcome] += $homeProbability * $awayProbability;
            }
        }
        $covered = array_sum($totals);
        $totals['covered'] = $covered;
        $totals['omitted'] = max(0.0, 1.0 - $covered);

        return $totals;
    }

    /** @return list<float> */
    private static function probabilities(float $mean, int $maxGoals): array
    {
        $values = [exp(-$mean)];
        for ($goals = 1; $goals <= $maxGoals; $goals++) {
            $values[] = $values[$goals - 1] * $mean / $goals;
        }

        return $values;
    }
}
