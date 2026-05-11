<?php

namespace App\Services\Brokers\Kis;

use App\Contracts\MarketData\BrokerProvider;
use App\Services\Kis\KisStockMetaService;
use App\Services\Kis\KisStockService;
use RuntimeException;

class KisBrokerProvider implements BrokerProvider
{
    public function __construct(
        private KisStockService $stockService,
        private KisStockMetaService $stockMetaService,
    ) {}

    public function quote(string $code, string $venue = 'krx'): array
    {
        $result = $this->stockService->getPrice($code);

        if (($result['success'] ?? true) === false) {
            throw new RuntimeException($result['message'] ?? 'KIS quote request failed.');
        }

        $turnoverAmount = (int) ($result['turnoverAmount'] ?? 0);
        if ($turnoverAmount <= 0) {
            $turnoverAmount = (int) (($result['price'] ?? 0) * ($result['volume'] ?? 0));
        }

        return [
            'code' => $result['code'] ?? $code,
            'price' => (int) ($result['price'] ?? 0),
            'change' => (int) ($result['change'] ?? 0),
            'changeRate' => (float) ($result['changeRate'] ?? 0),
            'volume' => (int) ($result['volume'] ?? 0),
            'turnover' => (int) round($turnoverAmount / 100_000_000),
            'turnoverAmount' => $turnoverAmount,
            'open' => (int) ($result['open'] ?? 0),
            'high' => (int) ($result['high'] ?? 0),
            'low' => (int) ($result['low'] ?? 0),
            'per' => (float) ($result['per'] ?? 0),
            'pbr' => (float) ($result['pbr'] ?? 0),
            'asOf' => now()->toIso8601String(),
        ];
    }

    public function chart(string $code, string $interval, array $options = []): array
    {
        return [];
    }

    public function orderbook(string $code, string $venue = 'krx'): array
    {
        return [];
    }

    public function trades(string $code, string $venue = 'krx', int $limit = 30): array
    {
        return [];
    }

    public function rankings(string $type, string $market = 'all', int $limit = 10): array
    {
        return [];
    }

    public function syncStockMaster(array $options = []): array
    {
        return $this->stockMetaService->import(
            markets: $options['markets'] ?? [],
            includeNxt: (bool) ($options['include_nxt'] ?? false),
            chunkSize: $options['chunk_size'] ?? null,
            useUpsert: $options['use_upsert'] ?? null,
        );
    }
}
