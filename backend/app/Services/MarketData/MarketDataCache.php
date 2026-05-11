<?php

namespace App\Services\MarketData;

use Closure;
use Illuminate\Support\Facades\Cache;

class MarketDataCache
{
    public function rankingKey(string $market, int $limit, array $types): string
    {
        sort($types);

        return sprintf(
            'market:ranking:%s:%d:%s',
            $market,
            $limit,
            implode(',', $types)
        );
    }

    public function rankingUniverseKey(string $market): string
    {
        return "market:ranking-universe:{$market}";
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
