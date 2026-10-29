<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction;

use InvalidArgumentException;

/** Decimal odds calculations. Probabilities are fractions, not percentages. */
final class Odds
{
    public static function impliedProbability(float $decimalOdds): float
    {
        if (!is_finite($decimalOdds) || $decimalOdds < 1.0) {
            throw new InvalidArgumentException('Decimal odds must be finite and at least 1.0.');
        }

        return 1.0 / $decimalOdds;
    }

    /**
     * Proportional normalization assumes the given outcomes are exhaustive and
     * mutually exclusive. It does not estimate the bookmaker's true probabilities.
     *
     * @param array<string, int|float> $odds
     * @return array{implied: array<string, float>, overround: float, normalized: array<string, float>}
     */
    public static function market(array $odds): array
    {
        if (count($odds) < 2) {
            throw new InvalidArgumentException('A market needs at least two outcomes.');
        }

        $implied = [];
        foreach ($odds as $outcome => $odd) {
            if (!is_string($outcome) || trim($outcome) === '' || (!is_int($odd) && !is_float($odd))) {
                throw new InvalidArgumentException('Each outcome needs a nonempty name and numeric decimal odds.');
            }
            $implied[$outcome] = self::impliedProbability((float) $odd);
        }

        $total = array_sum($implied);
        $normalized = [];
        foreach ($implied as $outcome => $probability) {
            $normalized[$outcome] = $probability / $total;
        }

        return ['implied' => $implied, 'overround' => $total - 1.0, 'normalized' => $normalized];
    }
}
