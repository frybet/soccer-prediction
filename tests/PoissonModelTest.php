<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction\Tests;

use Frybet\SoccerPrediction\PoissonModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PoissonModelTest extends TestCase
{
    public function testKnownScoresAndAnalyticalMarkets(): void
    {
        $model = new PoissonModel(1.0, 1.0);
        self::assertEqualsWithDelta(exp(-2.0), $model->exactScore(1, 1), 1.0e-12);
        self::assertEqualsWithDelta((1.0 - exp(-1.0)) ** 2, $model->bothTeamsToScore(), 1.0e-12);
        self::assertEqualsWithDelta(1.0 - 5.0 * exp(-2.0), $model->overTwoPointFive(), 1.0e-12);
        self::assertSame(1, $model->mostLikelyScore()['home']);
        self::assertSame(1, $model->mostLikelyScore()['away']);
    }

    public function testZeroRatesAndTinyRates(): void
    {
        $zero = new PoissonModel(0.0, 0.0);
        self::assertSame(1.0, $zero->exactScore(0, 0));
        self::assertSame(0.0, $zero->bothTeamsToScore());
        self::assertSame(0.0, $zero->overTwoPointFive());
        self::assertSame(1.0, $zero->outcomes(0)['draw']);
        $tiny = new PoissonModel(0.0000005, 0.0000005);
        self::assertGreaterThan(0.0, $tiny->overTwoPointFive());
        self::assertEqualsWithDelta(1.0e-18 / 6.0, $tiny->overTwoPointFive(), 1.0e-24);
    }

    public function testTruncatedGridReportsUncoveredProbabilityWithoutRenormalizing(): void
    {
        $model = new PoissonModel(1.5, 1.5);
        $grid = $model->outcomes(2);
        self::assertEqualsWithDelta($grid['home'], $grid['away'], 1.0e-12);
        self::assertEqualsWithDelta($grid['covered'], $grid['home'] + $grid['draw'] + $grid['away'], 1.0e-12);
        self::assertGreaterThan(0.0, $grid['omitted']);
        self::assertEqualsWithDelta(1.0, $grid['covered'] + $grid['omitted'], 1.0e-12);
        self::assertGreaterThan($grid['covered'], $model->outcomes(10)['covered']);
    }

    public function testInvalidParametersAreRejected(): void
    {
        foreach ([-1.0, INF, NAN, 101.0] as $mean) {
            try {
                new PoissonModel($mean, 1.0);
                self::fail('Invalid mean must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        $this->expectException(InvalidArgumentException::class);
        (new PoissonModel(1.0, 1.0))->exactScore(-1, 0);
    }
}
