<?php

namespace App\Console\Commands;

use App\Services\MarketData\MarketRankingService;
use Illuminate\Console\Command;

class RefreshStockRankingsCommand extends Command
{
    protected $signature = 'stocks:refresh-rankings
        {--market=* : 갱신할 시장(all, kospi, kosdaq, konex). 생략 시 all/kospi/kosdaq}
        {--limit=10 : 랭킹별 종목 수}
        {--type=* : 갱신할 랭킹 타입(gainers, losers, volume, turnover). 생략 시 전체}';

    protected $description = '국내주식 Top10 랭킹 캐시를 갱신합니다';

    public function handle(MarketRankingService $rankings): int
    {
        $markets = $this->option('market') ?: ['all', 'kospi', 'kosdaq'];
        $types = $this->option('type') ?: [];
        $limit = (int) $this->option('limit');

        foreach ($markets as $market) {
            $result = $rankings->refresh($market, $limit, $types);

            $this->components->info(sprintf(
                '랭킹 캐시 갱신 완료: market=%s source=%s asOf=%s',
                $result['market'],
                $result['source'],
                $result['asOf']
            ));
        }

        return self::SUCCESS;
    }
}
