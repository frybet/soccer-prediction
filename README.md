# Soccer Prediction for PHP

Small, standalone tools for football form statistics, decimal odds and a
transparent independent Poisson baseline. There is no database, HTTP client,
website runtime, provider account or production data dependency.

This library deliberately excludes FryBet's
proprietary prediction formulas, selection thresholds and provider integrations.
The Poisson model is a general baseline; its predictions are not FryBet's picks.

## Requirements

- PHP 8.0 or newer.
- Composer 2 for installation and the development test suite.
- PHPUnit's development dependencies require the usual XML and mbstring extensions.

## Install and run locally

Clone or obtain this repository and run:

```sh
git clone https://github.com/frybet/soccer-prediction.git
cd soccer-prediction
composer install
composer test
php examples/analysis.php
```

To use the library in another Composer project, add the GitHub repository to the
application's `composer.json`:

```json
{
  "repositories": [
    {"type": "vcs", "url": "https://github.com/frybet/soccer-prediction"}
  ],
  "require": {
    "frybet/soccer-prediction": "dev-main"
  }
}
```

Then run `composer update` in the consuming application and load
`vendor/autoload.php`. The package is not assumed to be available on Packagist.

## Decimal odds and double chance

Probabilities are fractions in the range 0–1, not percentages.

```php
use Frybet\SoccerPrediction\Odds;
use Frybet\SoccerPrediction\OutcomeProbabilities;

$market = Odds::market(['home' => 2.0, 'draw' => 3.0, 'away' => 4.0]);
$rawHome = $market['implied']['home'];       // 0.5
$margin = $market['overround'];               // sum(1 / odds) - 1
$homeDrawAway = $market['normalized'];         // proportional normalization
$doubleChance = OutcomeProbabilities::doubleChance($homeDrawAway);
// $doubleChance: 1X = home + draw, X2 = draw + away, 12 = home + away
```

`Odds::market()` assumes the supplied outcomes cover a whole market and are
mutually exclusive. Overround can be negative for an underround. Normalized
probabilities are a simple allocation method, not verified fair odds or a
calibrated prediction. Decimal odds must be finite and at least 1.0.
`doubleChance()` requires home, draw and away probabilities that sum to 1.

## Correct score, BTTS and over 2.5

```php
use Frybet\SoccerPrediction\PoissonModel;

// Supply your own expected goals; these example values are assumptions.
$model = new PoissonModel(1.5, 1.2);
$probabilityOfTwoOne = $model->exactScore(2, 1);
$mostLikely = $model->mostLikelyScore(); // one mode; see tie note below
$bothTeamsScore = $model->bothTeamsToScore();
$over25 = $model->overTwoPointFive();
$outcomes = $model->outcomes(10);
// home, draw and away are unnormalized probabilities in the 0..10 score grid.
// covered + omitted = 1, allowing for floating-point rounding.
```

The model assumes each side's goals follow independent Poisson distributions.
It does not infer expected goals from odds, form, or any FryBet data. For a
positive integer expected goal value there are two equally likely goal counts;
`mostLikelyScore()` returns the higher one. The score-grid outcome estimates
exclude scores above the supplied cap, so inspect `omitted` before using them.
For exact 1X/X2/12 combinations, supply a complete probability distribution to
`OutcomeProbabilities::doubleChance()` rather than treating truncated totals
as a complete distribution.

## Recent team form from arrays or JSON

```php
use Frybet\SoccerPrediction\TeamForm;

$json = file_get_contents('examples/sample-matches.json');
$matches = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$form = TeamForm::summarize($matches, 'North FC', 5);
// played, wins, draws, losses, goals_for, goals_against,
// btts_matches, over_2_5_matches, per-match goal averages and rates
```

Completed matches use `home_team`, `away_team`, nonnegative integer
`home_goals` and `away_goals`, and an ISO 8601 `played_at` timestamp
including seconds and a timezone. `status` defaults to `played`; other
statuses are ignored. Eligible results are ordered newest first before the
limit is applied. Names are matched exactly; resolve aliases or provider IDs
before calling. Duplicate input matches are counted as supplied. An empty
history returns zero counts and `null` rates. Invalid relevant data throws
`InvalidArgumentException` instead of fabricating results.

All examples use fictional clubs and invented data. See
[algorithm audit](docs/ALGORITHM_AUDIT.md) for the website areas inspected
and the boundaries of this extraction.

## Tests and license

`composer test` runs the PHPUnit suite. CI is configured for PHP 8.0 through 8.5.
The project is intended for analytical and educational use; outputs are model
estimates, not guarantees. Code in this project is offered under the
[MIT license](LICENSE).
