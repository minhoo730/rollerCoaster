<?php

namespace App\Services\MarketData;

use App\Contracts\MarketData\BrokerProvider;
use App\Models\Stocks\Stock;
use Illuminate\Support\Collection;
use Throwable;

class MarketRankingService
{
    public function __construct(
        private BrokerProvider $provider,
        private MarketDataCache $cache,
    ) {}

    public function top(string $market = 'all', int $limit = 10, array $types = []): array
    {
        [$market, $limit, $types] = $this->normalizeOptions($market, $limit, $types);

        $key = $this->cache->rankingKey($market, $limit, $types);
        $cacheHit = $this->cache->has($key);
        $ttl = $this->rankingTtl();

        $payload = $this->cache->remember(
            $key,
            $ttl,
            fn () => $this->buildPayload($market, $limit, $types)
        );

        $payload['cache'] = [
            'hit' => $cacheHit,
            'ttl' => $ttl,
            'key' => $key,
        ];

        return $payload;
    }

    public function refresh(string $market = 'all', int $limit = 10, array $types = []): array
    {
        [$market, $limit, $types] = $this->normalizeOptions($market, $limit, $types);

        $this->cache->forget($this->cache->rankingUniverseKey($market));
        $this->cache->forget($this->cache->rankingKey($market, $limit, $types));

        return $this->top($market, $limit, $types);
    }

    public function validMarkets(): array
    {
        return config('market_data.rankings.markets', ['all', 'kospi', 'kosdaq', 'konex']);
    }

    public function validTypes(): array
    {
        return config('market_data.rankings.types', ['gainers', 'losers', 'volume', 'turnover']);
    }

    private function buildPayload(string $market, int $limit, array $types): array
    {
        $universe = $this->quoteUniverse($market);
        $items = collect($universe['items']);
        $rankings = [];

        foreach ($types as $type) {
            $rankings[$type] = $this->rank($items, $type, $limit)->values()->all();
        }

        return [
            'market' => $market,
            'limit' => $limit,
            'rankings' => $rankings,
            'source' => $universe['source'],
            'asOf' => now()->toIso8601String(),
        ];
    }

    private function quoteUniverse(string $market): array
    {
        $key = $this->cache->rankingUniverseKey($market);
        $ttl = (int) config('market_data.cache.ranking_universe_ttl', 20);

        return $this->cache->remember($key, $ttl, function () use ($market) {
            $stocks = $this->stocksForRanking($market);
            $hasLiveQuote = false;

            $items = $stocks->map(function (Stock $stock) use (&$hasLiveQuote) {
                try {
                    $quote = $this->provider->quote($stock->code, $stock->venue);
                    $hasLiveQuote = true;
                    $this->pauseAfterProviderCall();

                    return $this->rankingRow($stock, $quote);
                } catch (Throwable $e) {
                    if (! str_contains($e->getMessage(), '설정 또는 토큰')) {
                        $this->pauseAfterProviderCall();
                    }

                    return $this->fallbackRow($stock);
                }
            })->all();

            return [
                'source' => $hasLiveQuote ? config('market_data.default_provider', 'kis') : 'stock_master',
                'items' => $items,
            ];
        });
    }

    private function stocksForRanking(string $market): Collection
    {
        $sampleSize = max(1, (int) config('market_data.rankings.sample_size', 60));

        if ($market !== 'all') {
            return $this->stockQuery()
                ->where('market', $market)
                ->limit($sampleSize)
                ->get(['code', 'name', 'market', 'venue', 'sector']);
        }

        $markets = ['kospi', 'kosdaq', 'konex'];
        $perMarket = (int) ceil($sampleSize / count($markets));

        return collect($markets)
            ->flatMap(fn (string $marketName) => $this->stockQuery()
                ->where('market', $marketName)
                ->limit($perMarket)
                ->get(['code', 'name', 'market', 'venue', 'sector']))
            ->take($sampleSize)
            ->values();
    }

    private function stockQuery()
    {
        return Stock::query()
            ->where('is_active', true)
            ->where('venue', 'krx')
            ->orderByRaw("CASE WHEN venue = 'krx' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE market WHEN 'kospi' THEN 0 WHEN 'kosdaq' THEN 1 WHEN 'konex' THEN 2 ELSE 3 END")
            ->orderBy('name');
    }

    private function rankingRow(Stock $stock, array $quote): array
    {
        return [
            'code' => $stock->code,
            'name' => $stock->name,
            'market' => $stock->market,
            'venue' => $stock->venue,
            'sector' => $stock->sector,
            'price' => (int) ($quote['price'] ?? 0),
            'change' => (int) ($quote['change'] ?? 0),
            'changeRate' => (float) ($quote['changeRate'] ?? 0),
            'volume' => (int) ($quote['volume'] ?? 0),
            'turnover' => (int) ($quote['turnover'] ?? 0),
            'turnoverAmount' => (int) ($quote['turnoverAmount'] ?? 0),
            'reason' => $stock->sector,
            'asOf' => $quote['asOf'] ?? now()->toIso8601String(),
        ];
    }

    private function fallbackRow(Stock $stock): array
    {
        return [
            'code' => $stock->code,
            'name' => $stock->name,
            'market' => $stock->market,
            'venue' => $stock->venue,
            'sector' => $stock->sector,
            'price' => 0,
            'change' => 0,
            'changeRate' => 0.0,
            'volume' => 0,
            'turnover' => 0,
            'turnoverAmount' => 0,
            'reason' => $stock->sector,
            'asOf' => now()->toIso8601String(),
        ];
    }

    private function rank(Collection $items, string $type, int $limit): Collection
    {
        return match ($type) {
            'gainers' => $items
                ->filter(fn (array $item) => $item['changeRate'] > 0)
                ->sortByDesc('changeRate')
                ->take($limit),
            'losers' => $items
                ->filter(fn (array $item) => $item['changeRate'] < 0)
                ->sortBy('changeRate')
                ->take($limit),
            'volume' => $items
                ->sortByDesc('volume')
                ->take($limit),
            'turnover' => $items
                ->sortByDesc('turnoverAmount')
                ->take($limit),
            default => collect(),
        };
    }

    private function normalizeOptions(string $market, int $limit, array $types): array
    {
        $validMarkets = $this->validMarkets();
        $validTypes = $this->validTypes();
        $defaultLimit = (int) config('market_data.rankings.default_limit', 10);
        $maxLimit = (int) config('market_data.rankings.max_limit', 50);

        $market = in_array($market, $validMarkets, true) ? $market : 'all';
        $limit = max(1, min($limit ?: $defaultLimit, $maxLimit));
        $types = array_values(array_intersect($types ?: $validTypes, $validTypes));

        return [$market, $limit, $types ?: $validTypes];
    }

    private function rankingTtl(): int
    {
        return max(1, (int) config('market_data.cache.rankings_ttl', 30));
    }

    private function pauseAfterProviderCall(): void
    {
        $milliseconds = max(0, (int) config('market_data.rankings.quote_delay_ms', 80));

        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
