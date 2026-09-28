<?php

namespace App\Console\Commands;

use App\Services\Kis\KisStockMetaService;
use Illuminate\Console\Command;

class ImportKisStocksCommand extends Command
{
    protected $signature = 'stocks:import-kis
        {--market=* : 가져올 시장(kospi, kosdaq, konex). 생략 시 전체}
        {--include-nxt : 설정된 NXT 마스터 소스도 함께 가져오기}
        {--chunk= : DB 저장 chunk 크기}
        {--use-update-or-create : bulk upsert 대신 updateOrCreate 사용}';

    protected $description = '한국투자증권/거래소 종목 마스터 파일을 내려받아 stocks 테이블을 갱신합니다';

    public function handle(KisStockMetaService $service): int
    {
        $chunkSize = $this->option('chunk') !== null ? (int) $this->option('chunk') : null;
        $result = $service->import(
            markets: $this->option('market'),
            includeNxt: (bool) $this->option('include-nxt'),
            chunkSize: $chunkSize,
            useUpsert: $this->option('use-update-or-create') ? false : null,
        );

        $this->components->info(sprintf(
            '종목 마스터 수집 완료: 처리 %d건, 스킵 %d건, 비활성화 %d건',
            $result['total_processed'], $result['total_skipped'], $result['total_deactivated']
        ));

        $this->table(
            ['source', 'market', 'venue', 'status', 'processed', 'skipped', 'message'],
            collect($result['sources'])->map(fn (array $source) => [
                $source['key'], $source['market'] ?? '-', $source['venue'] ?? '-', $source['status'],
                $source['processed'] ?? 0, $source['skipped'] ?? 0, $source['message'] ?? '',
            ])->all()
        );

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
