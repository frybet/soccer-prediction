<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction\Tests;

use Frybet\SoccerPrediction\TeamForm;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TeamFormTest extends TestCase
{
    public function testRecentPlayedMatchesSortByTimeAndRespectHomeAway(): void
    {
        $matches = [
            ['home_team' => 'Alpha', 'away_team' => 'Beta', 'home_goals' => 2, 'away_goals' => 1, 'played_at' => '2026-01-01T12:00:00Z'],
            ['home_team' => 'Beta', 'away_team' => 'Alpha', 'home_goals' => 3, 'away_goals' => 0, 'played_at' => '2026-01-03T12:00:00+00:00'],
            ['home_team' => 'Alpha', 'away_team' => 'Gamma', 'home_goals' => 2, 'away_goals' => 2, 'played_at' => '2026-01-02T12:00:00Z'],
            ['home_team' => 'Alpha', 'away_team' => 'Delta', 'status' => 'scheduled'],
            ['home_team' => 'Other', 'away_team' => 'Delta', 'home_goals' => 1, 'away_goals' => 1, 'played_at' => '2026-01-04T12:00:00Z'],
        ];
        $summary = TeamForm::summarize($matches, 'Alpha', 2);
        self::assertSame(2, $summary['played']);
        self::assertSame(0, $summary['wins']);
        self::assertSame(1, $summary['draws']);
        self::assertSame(1, $summary['losses']);
        self::assertSame(2, $summary['goals_for']);
        self::assertSame(5, $summary['goals_against']);
        self::assertSame(1, $summary['btts_matches']);
        self::assertSame(2, $summary['over_2_5_matches']);
        self::assertEqualsWithDelta(0.5, $summary['btts_rate'], 1.0e-12);
    }

    public function testEmptyHistoryAndExactTeamIdentity(): void
    {
        $match = ['home_team' => 'United Women', 'away_team' => 'United', 'home_goals' => 2, 'away_goals' => 1, 'played_at' => '2026-01-01T12:00:00Z'];
        $summary = TeamForm::summarize([$match], 'United');
        self::assertSame(1, $summary['goals_for']);
        self::assertSame(2, $summary['goals_against']);
        $empty = TeamForm::summarize([$match], 'United U21');
        self::assertSame(0, $empty['played']);
        self::assertNull($empty['btts_rate']);
    }

    public function testInvalidPlayedScoresAndDatesAreRejected(): void
    {
        $base = ['home_team' => 'A', 'away_team' => 'B', 'home_goals' => 1, 'away_goals' => 0, 'played_at' => '2026-01-01T12:00:00Z'];
        foreach ([['home_goals' => -1], ['home_goals' => '1'], ['played_at' => '2026-02-30T00:00:00Z'], ['played_at' => 'yesterday'], ['played_at' => '2026-01-01T00:00:00+99:99']] as $patch) {
            try {
                TeamForm::summarize([array_replace($base, $patch)], 'A');
                self::fail('Invalid match must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }
}
