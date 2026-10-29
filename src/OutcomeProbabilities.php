<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction;

use InvalidArgumentException;

final class OutcomeProbabilities
{
    /**
     * Combine a complete set of mutually exclusive home/draw/away probabilities.
     * @param array{home: float|int, draw: float|int, away: float|int} $outcomes
     * @return array{1X: float, X2: float, 12: float}
     */
    public static function doubleChance(array $outcomes): array
    {
        if (count($outcomes) !== 3 || array_diff(['home', 'draw', 'away'], array_keys($outcomes))) {
            throw new InvalidArgumentException('Provide exactly home, draw and away probabilities.');
        }
        foreach ($outcomes as $probability) {
            if ((!is_int($probability) && !is_float($probability))
                || !is_finite((float) $probability) || $probability < 0 || $probability > 1) {
                throw new InvalidArgumentException('Probabilities must be finite numbers between 0 and 1.');
            }
        }
        if (abs(array_sum($outcomes) - 1.0) > 1.0e-9) {
            throw new InvalidArgumentException('Outcome probabilities must sum to 1.');
        }

        return [
            '1X' => $outcomes['home'] + $outcomes['draw'],
            'X2' => $outcomes['draw'] + $outcomes['away'],
            '12' => $outcomes['home'] + $outcomes['away'],
        ];
    }
}
