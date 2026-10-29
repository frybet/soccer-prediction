# Algorithm selection audit

The library provides independently implemented, reusable football statistics.
Its model choices are deliberately simple and documented.

| Area | Library implementation |
| --- | --- |
| Recent form | Summarizes goals, wins, draws, losses and observed BTTS/over 2.5 rates from caller-provided match data. |
| Decimal odds | Computes 1 / decimal odds and optional proportional market normalization. |
| Double chance | Combines complete, mutually exclusive home, draw and away probabilities. |
| Correct score, BTTS and over 2.5 | Uses a standard independent Poisson baseline with caller-supplied expected goals. |

The synthetic sample uses fictional team names and invented numbers. There are
no website/database dependencies, third-party article text, website branding
assets, API keys, credentials or production records in this candidate.

The library does not reproduce FryBet's website picks or promise their accuracy.
