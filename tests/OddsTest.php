<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction\Tests;

use Frybet\SoccerPrediction\Odds;
use Frybet\SoccerPrediction\OutcomeProbabilities;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OddsTest extends TestCase
{
    public function testImpliedProbabilityAndProportionalNormalization(): void
    {
        self::assertEqualsWithDelta(0.5, Odds::impliedProbability(2.0), 1.0e-12);
        $market = Odds::market(['home' => 2.0, 'draw' => 4.0, 'away' => 4.0]);
        self::assertEqualsWithDelta(0.0, $market['overround'], 1.0e-12);
        self::assertEqualsWithDelta(1.0, array_sum($market['normalized']), 1.0e-12);
        self::assertSame(['home', 'draw', 'away'], array_keys($market['normalized']));
        self::assertEqualsWithDelta(0.75, OutcomeProbabilities::doubleChance($market['normalized'])['1X'], 1.0e-12);
    }

    public function testBookmakerMarginIsNotConfusedWithTheNormalizedProbability(): void
    {
        $market = Odds::market(['home' => 2.0, 'draw' => 3.0, 'away' => 4.0]);
        self::assertEqualsWithDelta(1.0 / 12.0, $market['overround'], 1.0e-12);
        self::assertEqualsWithDelta(6.0 / 13.0, $market['normalized']['home'], 1.0e-12);
        $double = OutcomeProbabilities::doubleChance($market['normalized']);
        self::assertEqualsWithDelta($market['normalized']['home'] + $market['normalized']['draw'], $double['1X'], 1.0e-12);
        self::assertEqualsWithDelta(1 - $market['normalized']['home'], $double['X2'], 1.0e-12);
        self::assertEqualsWithDelta(1 - $market['normalized']['draw'], $double['12'], 1.0e-12);
    }

    public function testInvalidInputsAreRejected(): void
    {
        foreach ([0.0, -1.0, 0.99, INF, NAN] as $odd) {
            try {
                Odds::impliedProbability($odd);
                self::fail('Invalid odds must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        foreach ([['home' => 2.0], ['home' => '2.0', 'away' => 3.0]] as $market) {
            try {
                Odds::market($market);
                self::fail('Invalid market must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testDoubleChanceRejectsIncompleteAndInvalidProbabilitySets(): void
    {
        foreach ([['home' => .5, 'draw' => .2], ['home' => .5, 'draw' => .2, 'away' => .2],
            ['home' => -0.1, 'draw' => .5, 'away' => .6],
            ['home' => NAN, 'draw' => .5, 'away' => .5]] as $outcomes) {
            try {
                OutcomeProbabilities::doubleChance($outcomes);
                self::fail('Invalid outcomes must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }
}
