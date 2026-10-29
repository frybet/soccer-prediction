<?php

declare(strict_types=1);

namespace Frybet\SoccerPrediction;

use DateTimeImmutable;
use InvalidArgumentException;

final class TeamForm
{
    /**
     * Calculate recent played matches, ordered newest first. Team names must
     * match exactly; callers should resolve provider IDs or aliases beforehand.
     *
     * @param list<array<string, mixed>> $matches
     * @return array<string, int|float|null>
     */
    public static function summarize(array $matches, string $team, int $limit = 5): array
    {
        if (trim($team) === '' || $limit < 1) {
            throw new InvalidArgumentException('Team must be nonempty and limit must be positive.');
        }
        $eligible = [];
        foreach ($matches as $index => $match) {
            if (!is_array($match)) {
                throw new InvalidArgumentException('Every match must be an array.');
            }
            $status = $match['status'] ?? 'played';
            if (!is_string($status)) {
                throw new InvalidArgumentException('Status must be a string.');
            }
            if ($status !== 'played') {
                continue;
            }
            $home = $match['home_team'] ?? null;
            $away = $match['away_team'] ?? null;
            if (!is_string($home) || !is_string($away) || trim($home) === '' || trim($away) === '' || $home === $away) {
                throw new InvalidArgumentException('Played matches require two different nonempty team names.');
            }
            if ($home !== $team && $away !== $team) {
                continue;
            }
            $homeGoals = $match['home_goals'] ?? null;
            $awayGoals = $match['away_goals'] ?? null;
            if (!is_int($homeGoals) || !is_int($awayGoals) || $homeGoals < 0 || $awayGoals < 0) {
                throw new InvalidArgumentException('Played match scores must be nonnegative integers.');
            }
            $date = $match['played_at'] ?? null;
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $date)) {
                throw new InvalidArgumentException('played_at must be an ISO 8601 timestamp with a timezone.');
            }
            if (preg_match('/[+-](\d{2}):(\d{2})$/D', $date, $offset)
                && ((int)$offset[1] > 23 || (int)$offset[2] > 59)) {
                throw new InvalidArgumentException('played_at has an invalid timezone offset.');
            }
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i:sP', $date);
            if ($parsed === false || DateTimeImmutable::getLastErrors() !== false) {
                throw new InvalidArgumentException('played_at must contain a valid date and time.');
            }
            $eligible[] = [
                'timestamp' => $parsed->getTimestamp(),
                'index' => $index,
                'for' => $home === $team ? $homeGoals : $awayGoals,
                'against' => $home === $team ? $awayGoals : $homeGoals,
            ];
        }
        usort($eligible, static function (array $a, array $b): int {
            return ($b['timestamp'] <=> $a['timestamp']) ?: ($a['index'] <=> $b['index']);
        });
        $recent = array_slice($eligible, 0, $limit);
        $stats = ['played' => count($recent), 'wins' => 0, 'draws' => 0, 'losses' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'btts_matches' => 0, 'over_2_5_matches' => 0];
        foreach ($recent as $match) {
            $result = $match['for'] > $match['against'] ? 'wins'
                : ($match['for'] < $match['against'] ? 'losses' : 'draws');
            $stats[$result]++;
            $stats['goals_for'] += $match['for'];
            $stats['goals_against'] += $match['against'];
            $stats['btts_matches'] += (int) ($match['for'] > 0 && $match['against'] > 0);
            $stats['over_2_5_matches'] += (int) ($match['for'] + $match['against'] >= 3);
        }
        $stats['goals_for_per_match'] = $stats['played'] ? $stats['goals_for'] / $stats['played'] : null;
        $stats['goals_against_per_match'] = $stats['played'] ? $stats['goals_against'] / $stats['played'] : null;
        $stats['btts_rate'] = $stats['played'] ? $stats['btts_matches'] / $stats['played'] : null;
        $stats['over_2_5_rate'] = $stats['played'] ? $stats['over_2_5_matches'] / $stats['played'] : null;

        return $stats;
    }
}
