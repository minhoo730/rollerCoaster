<?php

namespace App\Services\Kis;

use App\Models\Stocks\Stock;
use DOMDocument;
use DOMXPath;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

class KisStockMetaService
{
    private const VALID_MARKETS = ['kospi', 'kosdaq', 'konex'];

    private const VALID_VENUES = ['krx', 'nxt'];

    private const ENCODING_CANDIDATES = ['EUC-KR', 'CP949', 'UTF-8'];

    private const DEFAULT_SOURCE_ENCODING = 'CP949';

    private ?array $krxMarketMap = null;

    public function import(
        array $markets = [],
        bool $includeNxt = false,
        ?int $chunkSize = null,
        ?bool $useUpsert = null
    ): array {
        $chunkSize = max(1, $chunkSize ?? (int) config('kis.stock_meta.chunk_size', 1000));
        $useUpsert = $useUpsert ?? (bool) config('kis.stock_meta.use_upsert', true);
        $requestedMarkets = $this->normalizeMarkets($markets);
        $sources = $this->buildSources($requestedMarkets, $includeNxt);

        $summary = [
            'success' => false,
            'started_at' => now()->toISOString(),
            'finished_at' => null,
            'requested_markets' => $requestedMarkets,
            'include_nxt' => $includeNxt,
            'chunk_size' => $chunkSize,
            'write_mode' => $useUpsert ? 'upsert' : 'update_or_create',
            'total_processed' => 0,
            'total_skipped' => 0,
            'total_deactivated' => 0,
            'sources' => [],
        ];

        foreach ($sources as $sourceKey => $source) {
            $source['key'] = $sourceKey;
            $sourceResult = [
                'key' => $sourceKey,
                'market' => $source['market'] ?? null,
                'venue' => $source['venue'] ?? null,
                'provider' => $source['provider'] ?? null,
                'format' => $source['format'] ?? null,
                'status' => 'pending',
                'message' => null,
                'processed' => 0,
                'skipped' => 0,
                'deactivated' => 0,
            ];

            if (empty($source['url'])) {
                $sourceResult['status'] = 'skipped';
                $sourceResult['message'] = '마스터 파일 URL이 설정되지 않았습니다.';
                $summary['sources'][] = $sourceResult;

                Log::notice('KIS stock meta import source skipped', [
                    'source' => $sourceKey,
                    'market' => $sourceResult['market'],
                    'venue' => $sourceResult['venue'],
                    'message' => $sourceResult['message'],
                ]);

                continue;
            }

            try {
                $filePath = $this->downloadMasterFile($source);
                $result = $this->persistRecords(
                    $this->parseFile($filePath, $source),
                    $source,
                    $chunkSize,
                    $useUpsert
                );

                $status = $result['processed'] > 0 ? 'success' : 'empty';

                $sourceResult = array_merge($sourceResult, $result, [
                    'status' => $status,
                    'message' => $status === 'success'
                        ? '종목 마스터를 정상 처리했습니다.'
                        : '파싱된 종목 데이터가 없습니다.',
                    'file' => basename($filePath),
                ]);

                $summary['total_processed'] += $result['processed'];
                $summary['total_skipped'] += $result['skipped'];
                $summary['total_deactivated'] += $result['deactivated'];

                Log::info('KIS stock meta import source finished', [
                    'source' => $sourceKey,
                    'market' => $sourceResult['market'],
                    'venue' => $sourceResult['venue'],
                    'status' => $sourceResult['status'],
                    'processed' => $sourceResult['processed'],
                    'skipped' => $sourceResult['skipped'],
                    'deactivated' => $sourceResult['deactivated'],
                ]);
            } catch (Throwable $e) {
                report($e);

                $sourceResult['status'] = 'failed';
                $sourceResult['message'] = $e->getMessage();

                Log::warning('KIS stock meta import source failed', [
                    'source' => $sourceKey,
                    'market' => $sourceResult['market'],
                    'venue' => $sourceResult['venue'],
                    'message' => $e->getMessage(),
                ]);
            }

            $summary['sources'][] = $sourceResult;
        }

        $summary['finished_at'] = now()->toISOString();
        $summary['success'] = $summary['total_processed'] > 0
            && collect($summary['sources'])->every(fn (array $source) => in_array($source['status'], ['success', 'skipped'], true));

        return $summary;
    }

    private function normalizeMarkets(array $markets): array
    {
        if ($markets === []) {
            return array_keys((array) config('kis.stock_meta.markets', []));
        }

        return collect($markets)
            ->map(fn ($market) => strtolower((string) $market))
            ->filter(fn ($market) => in_array($market, self::VALID_MARKETS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function buildSources(array $markets, bool $includeNxt): array
    {
        $configuredMarkets = (array) config('kis.stock_meta.markets', []);
        $sources = [];

        foreach ($markets as $market) {
            if (isset($configuredMarkets[$market])) {
                $sources[$market] = $configuredMarkets[$market];
            }
        }

        $nxtSource = (array) config('kis.stock_meta.nxt', []);
        if ($includeNxt || ($nxtSource['enabled'] ?? false)) {
            $sources['nxt'] = $nxtSource;
        }

        return $sources;
    }

    private function downloadMasterFile(array $source): string
    {
        $baseDir = storage_path(sprintf(
            'app/%s/%s_%s',
            trim((string) config('kis.stock_meta.directory', 'kis/stock-meta'), '/'),
            now()->format('Ymd_His'),
            $source['key']
        ));

        File::ensureDirectoryExists($baseDir);

        $archive = $source['archive'] ?? null;
        $downloadPath = $baseDir . DIRECTORY_SEPARATOR . $source['key'] . ($archive === 'zip' ? '.zip' : '.dat');

        $response = Http::withHeaders([
            'User-Agent' => 'rollerCoaster stock-meta-importer/1.0',
            'Accept' => '*/*',
        ])
            ->timeout(60)
            ->retry(3, 1000)
            ->sink($downloadPath)
            ->get($source['url']);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                '마스터 파일 다운로드 실패 [%s]: HTTP %s',
                $source['key'],
                $response->status()
            ));
        }

        if (! File::exists($downloadPath) || File::size($downloadPath) === 0) {
            throw new RuntimeException(sprintf('마스터 파일이 비어 있습니다 [%s].', $source['key']));
        }

        if ($archive === 'zip') {
            return $this->extractZipArchive($downloadPath, $source);
        }

        return $downloadPath;
    }

    private function extractZipArchive(string $archivePath, array $source): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive 확장이 설치되어 있지 않습니다.');
        }

        $extractDir = dirname($archivePath) . DIRECTORY_SEPARATOR . 'extract';
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException("ZIP 파일을 열 수 없습니다: {$archivePath}");
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $configuredFileName = $source['file_name'] ?? null;
        if ($configuredFileName) {
            $configuredPath = $extractDir . DIRECTORY_SEPARATOR . $configuredFileName;
            if (File::exists($configuredPath)) {
                return $configuredPath;
            }
        }

        foreach (File::allFiles($extractDir) as $file) {
            if (strtolower($file->getExtension()) === 'mst') {
                return $file->getPathname();
            }
        }

        throw new RuntimeException("ZIP 안에서 MST 파일을 찾지 못했습니다: {$archivePath}");
    }

    private function parseFile(string $path, array $source): Generator
    {
        yield from match ($source['format'] ?? null) {
            'kis_mst' => $this->parseKisMst($path, $source),
            'kind_html' => $this->parseKindHtml($path, $source),
            default => throw new RuntimeException('지원하지 않는 종목 마스터 포맷입니다: ' . ($source['format'] ?? 'unknown')),
        };
    }

    private function parseKisMst(string $path, array $source): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("MST 파일을 열 수 없습니다: {$path}");
        }

        $preferredEncoding = (string) ($source['encoding'] ?? self::DEFAULT_SOURCE_ENCODING);
        $tailLength = (int) ($source['tail_length'] ?? 0);

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }

                $head = $tailLength > 0 && strlen($line) > $tailLength
                    ? substr($line, 0, strlen($line) - $tailLength)
                    : $line;

                if (strlen($head) < 22) {
                    continue;
                }

                $lineEncoding = $this->detectEncoding($head, $preferredEncoding);
                $code = $this->normalizeCode($this->decodeText(substr($head, 0, 9), $lineEncoding));
                $name = $this->normalizeName($this->decodeText(substr($head, 21), $lineEncoding));

                if ($code === null || $name === '') {
                    continue;
                }

                yield [
                    'code' => $code,
                    'name' => $name,
                    'market' => $source['market'] ?? null,
                    'venue' => $source['venue'] ?? 'krx',
                    'sector' => null,
                    'raw_payload' => [
                        'standard_code' => $this->decodeText(substr($head, 9, 12), $lineEncoding),
                        'encoding' => $lineEncoding,
                    ],
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    private function parseKindHtml(string $path, array $source): Generator
    {
        if (! class_exists(DOMDocument::class)) {
            throw new RuntimeException('KONEX HTML 파싱을 위해 PHP DOM 확장이 필요합니다.');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("KONEX HTML 파일을 읽을 수 없습니다: {$path}");
        }

        $html = $this->decodeHtml($contents, (string) ($source['encoding'] ?? 'EUC-KR'));
        $html = preg_replace('/charset=["\']?([a-zA-Z0-9_\-]+)/i', 'charset=UTF-8', $html) ?? $html;

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('KONEX HTML 문서를 DOM으로 파싱하지 못했습니다.');
        }

        $xpath = new DOMXPath($dom);
        $headers = [];
        $columnMap = null;

        foreach ($xpath->query('//tr') as $row) {
            $values = [];
            foreach ($xpath->query('./th|./td', $row) as $cell) {
                $values[] = $this->normalizeName($cell->textContent);
            }

            if ($values === []) {
                continue;
            }

            if ($headers === [] && collect($values)->contains(fn ($value) => $this->normalizeHeader($value) === '종목코드')) {
                $headers = array_map(fn ($value) => $this->normalizeHeader($value), $values);
                $columnMap = [
                    'code' => $this->findHeaderIndex($headers, ['종목코드', '단축코드']),
                    'name' => $this->findHeaderIndex($headers, ['회사명', '종목명', '한글명', '한글종목명', '기업명']),
                    'sector' => $this->findHeaderIndex($headers, ['업종', '업종명']),
                ];
                continue;
            }

            if ($columnMap === null || $columnMap['code'] === null || $columnMap['name'] === null) {
                continue;
            }

            $code = $this->normalizeCode($values[$columnMap['code']] ?? '');
            $name = $this->normalizeName($values[$columnMap['name']] ?? '');

            if ($code === null || $name === '') {
                continue;
            }

            yield [
                'code' => $code,
                'name' => $name,
                'market' => $source['market'] ?? null,
                'venue' => $source['venue'] ?? 'krx',
                'sector' => $columnMap['sector'] !== null
                    ? ($values[$columnMap['sector']] ?? null)
                    : null,
                'raw_payload' => [],
            ];
        }
    }

    private function persistRecords(iterable $records, array $source, int $chunkSize, bool $useUpsert): array
    {
        $syncedAt = now();
        $rows = [];
        $seen = [];
        $skipped = 0;

        foreach ($records as $record) {
            $row = $this->buildStockRow($record, $source, $syncedAt);
            if ($row === null) {
                $skipped++;
                continue;
            }

            $dedupeKey = $row['code'] . '|' . $row['venue'];
            if (isset($seen[$dedupeKey])) {
                $skipped++;
                continue;
            }

            $seen[$dedupeKey] = true;
            $rows[] = $row;
        }

        $processed = 0;
        $deactivated = 0;

        if ($rows !== []) {
            DB::transaction(function () use ($rows, $chunkSize, $useUpsert, $syncedAt, &$processed, &$deactivated) {
                foreach (array_chunk($rows, $chunkSize) as $chunk) {
                    $this->saveChunk($chunk, $useUpsert);
                    $processed += count($chunk);
                }

                $deactivated = $this->deactivateStaleStocks($rows, $syncedAt);
            });
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'deactivated' => $deactivated,
        ];
    }

    private function deactivateStaleStocks(array $rows, $syncedAt): int
    {
        $deactivated = 0;

        collect($rows)
            ->groupBy(fn (array $row) => $row['market'] . '|' . $row['venue'])
            ->each(function ($group, string $key) use (&$deactivated, $syncedAt) {
                [$market, $venue] = explode('|', $key, 2);
                $codes = $group
                    ->pluck('code')
                    ->unique()
                    ->values()
                    ->all();

                if ($codes === []) {
                    return;
                }

                $deactivated += Stock::query()
                    ->where('market', $market)
                    ->where('venue', $venue)
                    ->where('is_active', true)
                    ->whereNotIn('code', $codes)
                    ->update([
                        'is_active' => false,
                        'updated_at' => $syncedAt,
                    ]);
            });

        return $deactivated;
    }

    private function buildStockRow(array $record, array $source, $syncedAt): ?array
    {
        $code = $this->normalizeCode($record['code'] ?? '');
        $venue = strtolower((string) ($record['venue'] ?? $source['venue'] ?? 'krx'));
        $market = strtolower((string) ($record['market'] ?? $source['market'] ?? ''));

        if ($market === '' && $venue === 'nxt' && $code !== null) {
            $market = $this->marketForCode($code) ?? '';
        }

        if (
            $code === null
            || ! in_array($market, self::VALID_MARKETS, true)
            || ! in_array($venue, self::VALID_VENUES, true)
        ) {
            return null;
        }

        $name = $this->normalizeName((string) ($record['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $rawPayload = array_filter([
            'provider' => $source['provider'] ?? null,
            'source_key' => $source['key'] ?? null,
            ...($record['raw_payload'] ?? []),
        ], fn ($value) => $value !== null && $value !== '');

        $sector = $record['sector'] ?? null;

        return [
            'code' => $code,
            'name' => $name,
            'market' => $market,
            'venue' => $venue,
            'sector' => $sector ? $this->normalizeName((string) $sector) : null,
            'is_active' => true,
            'source' => $source['provider'] ?? 'kis',
            'raw_payload' => $rawPayload === []
                ? null
                : json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'last_synced_at' => $syncedAt,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ];
    }

    private function saveChunk(array $rows, bool $useUpsert): void
    {
        if ($useUpsert) {
            Stock::query()->upsert(
                $rows,
                ['code', 'venue'],
                [
                    'name',
                    'market',
                    'sector',
                    'is_active',
                    'source',
                    'raw_payload',
                    'last_synced_at',
                    'updated_at',
                ]
            );

            return;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $attributes = $row;
                unset($attributes['code'], $attributes['venue'], $attributes['created_at'], $attributes['updated_at']);

                if (is_string($attributes['raw_payload'] ?? null)) {
                    $attributes['raw_payload'] = json_decode($attributes['raw_payload'], true);
                }

                Stock::updateOrCreate(
                    [
                        'code' => $row['code'],
                        'venue' => $row['venue'],
                    ],
                    $attributes
                );
            }
        });
    }

    private function marketForCode(string $code): ?string
    {
        if ($this->krxMarketMap === null) {
            $this->krxMarketMap = Stock::query()
                ->where('venue', 'krx')
                ->whereIn('market', self::VALID_MARKETS)
                ->pluck('market', 'code')
                ->all();
        }

        return $this->krxMarketMap[$code] ?? null;
    }

    private function decodeHtml(string $value, ?string $fallbackEncoding = null): string
    {
        if (preg_match('/charset=["\']?([a-zA-Z0-9_\-]+)/i', $value, $matches)) {
            $value = str_replace("\0", '', $value);
            $encoding = $this->normalizeEncoding($matches[1]) ?? $fallbackEncoding;

            return trim($encoding === 'UTF-8'
                ? $value
                : $this->convertToUtf8($value, $encoding ?? self::DEFAULT_SOURCE_ENCODING));
        }

        return $this->decodeText($value, $fallbackEncoding);
    }

    private function decodeText(string $value, ?string $preferredEncoding = null): string
    {
        $value = str_replace("\0", '', $value);
        if ($value === '') {
            return '';
        }

        $encoding = $this->detectEncoding($value, $preferredEncoding);
        if ($encoding === 'UTF-8') {
            return trim($value);
        }

        return trim($this->convertToUtf8($value, $encoding));
    }

    private function detectEncoding(string $value, ?string $preferredEncoding = null): string
    {
        $candidates = $this->encodingCandidates($preferredEncoding);

        if ($this->isAscii($value)) {
            return $this->normalizeEncoding($preferredEncoding) ?? self::DEFAULT_SOURCE_ENCODING;
        }

        if (function_exists('mb_check_encoding')) {
            foreach ($candidates as $encoding) {
                try {
                    if (@mb_check_encoding($value, $encoding)) {
                        return $encoding;
                    }
                } catch (Throwable) {
                    continue;
                }
            }
        }

        if (function_exists('mb_detect_encoding')) {
            try {
                $detected = @mb_detect_encoding($value, $candidates, true);
                if (is_string($detected) && $detected !== '') {
                    return $this->normalizeEncoding($detected) ?? self::DEFAULT_SOURCE_ENCODING;
                }
            } catch (Throwable) {
                // 명시 감지 실패 시 기본 CP949로 변환을 시도합니다.
            }
        }

        return self::DEFAULT_SOURCE_ENCODING;
    }

    private function encodingCandidates(?string $preferredEncoding = null): array
    {
        $preferredEncoding = $this->normalizeEncoding($preferredEncoding);

        return collect([...self::ENCODING_CANDIDATES, $preferredEncoding])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function convertToUtf8(string $value, string $encoding): string
    {
        foreach ($this->conversionCandidates($encoding) as $candidate) {
            if ($candidate === 'UTF-8') {
                return $value;
            }

            if (function_exists('mb_convert_encoding')) {
                try {
                    return mb_convert_encoding($value, 'UTF-8', $candidate);
                } catch (Throwable) {
                    // 다음 후보 또는 iconv fallback으로 이어갑니다.
                }
            }

            if (function_exists('iconv')) {
                $converted = @iconv($candidate, 'UTF-8//IGNORE', $value);
                if ($converted !== false) {
                    return $converted;
                }
            }
        }

        return $value;
    }

    private function conversionCandidates(string $encoding): array
    {
        return collect([$this->normalizeEncoding($encoding), ...self::ENCODING_CANDIDATES])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeEncoding(?string $encoding): ?string
    {
        if ($encoding === null || trim($encoding) === '') {
            return null;
        }

        return match (strtoupper(str_replace('_', '-', trim($encoding)))) {
            'EUC-KR', 'EUCKR' => 'EUC-KR',
            'CP949', 'MS949', 'WINDOWS-949', 'UHC' => 'CP949',
            'UTF8', 'UTF-8' => 'UTF-8',
            default => strtoupper(trim($encoding)),
        };
    }

    private function isAscii(string $value): bool
    {
        return ! preg_match('/[\x80-\xFF]/', $value);
    }

    private function normalizeCode(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^KR[A-Z0-9](\d{6})/i', $value, $matches)) {
            return $matches[1];
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) < 6) {
            return str_pad($digits, 6, '0', STR_PAD_LEFT);
        }

        return strlen($digits) === 6 ? $digits : null;
    }

    private function normalizeName(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function normalizeHeader(string $value): string
    {
        return preg_replace('/\s+/u', '', $this->normalizeName($value)) ?? $value;
    }

    private function findHeaderIndex(array $headers, array $candidates): ?int
    {
        $normalizedCandidates = array_map(fn ($candidate) => $this->normalizeHeader($candidate), $candidates);

        foreach ($headers as $index => $header) {
            if (in_array($header, $normalizedCandidates, true)) {
                return $index;
            }
        }

        return null;
    }
}
