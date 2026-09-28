<?php

return [
    'default_provider' => env('MARKET_DATA_PROVIDER', 'kis'),
    'cache' => [
        'rankings_ttl' => (int) env('MARKET_DATA_RANKINGS_TTL', 300),
        'ranking_universe_ttl' => (int) env('MARKET_DATA_RANKING_UNIVERSE_TTL', 240),
    ],
    'rankings' => [
        'default_limit' => (int) env('MARKET_DATA_RANKINGS_LIMIT', 10),
        'max_limit' => (int) env('MARKET_DATA_RANKINGS_MAX_LIMIT', 50),
        'sample_size' => (int) env('MARKET_DATA_RANKINGS_SAMPLE_SIZE', 60),
        'quote_delay_ms' => (int) env('MARKET_DATA_RANKINGS_QUOTE_DELAY_MS', 80),
        'markets' => ['all', 'kospi', 'kosdaq', 'konex'],
        'types' => ['gainers', 'losers', 'volume', 'turnover'],
    ],
];
