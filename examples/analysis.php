<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Frybet\SoccerPrediction\Odds;
use Frybet\SoccerPrediction\OutcomeProbabilities;
use Frybet\SoccerPrediction\PoissonModel;
use Frybet\SoccerPrediction\TeamForm;

$json = file_get_contents(__DIR__ . '/sample-matches.json');
if ($json === false) {
    throw new RuntimeException('Sample file is unavailable.');
}
$matches = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$market = Odds::market(['home' => 2.1, 'draw' => 3.4, 'away' => 3.8]);

// These are example assumptions, not estimates from the supplied form.
$model = new PoissonModel(1.5, 1.2);
$result = [
    'form' => TeamForm::summarize($matches, 'North FC'),
    'implied_market' => $market,
    'double_chance' => OutcomeProbabilities::doubleChance($market['normalized']),
    'poisson' => [
        'most_likely_score' => $model->mostLikelyScore(),
        'btts' => $model->bothTeamsToScore(),
        'over_2_5' => $model->overTwoPointFive(),
        'outcomes' => $model->outcomes(),
    ],
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
