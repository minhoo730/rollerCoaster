<?php

namespace App\Contracts\MarketData;

interface BrokerProvider
{
    public function quote(string $code, string $venue = 'krx'): array;

    public function chart(string $code, string $interval, array $options = []): array;

    public function orderbook(string $code, string $venue = 'krx'): array;

    public function trades(string $code, string $venue = 'krx', int $limit = 30): array;

    public function rankings(string $type, string $market = 'all', int $limit = 10): array;

    public function syncStockMaster(array $options = []): array;
}
