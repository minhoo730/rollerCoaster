<?php

namespace App\Services\Kis;

use Illuminate\Support\Facades\Http;

class KisStockService
{
    public function getPrice(string $code): array
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return [
                'success' => false,
                'message' => '종목 코드는 6자리 숫자여야 합니다.',
                'statusCode' => 422,
            ];
        }

        $token = app(KisTokenService::class)->getToken();
        if (! $token) {
            return [
                'success' => false,
                'message' => '한국투자증권 API 설정 또는 토큰 발급에 실패했습니다.',
                'statusCode' => 503,
            ];
        }

        $response = Http::acceptJson()
            ->timeout((int) config('kis.timeout', 10))
            ->retry(2, 300)
            ->withHeaders([
                'Authorization' => "Bearer {$token}",
                'appkey' => config('kis.app_key'),
                'appsecret' => config('kis.app_secret'),
                'tr_id' => 'FHKST01010100',
            ])
            ->get(rtrim((string) config('kis.base_url'), '/').'/uapi/domestic-stock/v1/quotations/inquire-price', [
                'FID_COND_MRKT_DIV_CODE' => 'J',
                'FID_INPUT_ISCD' => $code,
            ]);

        $json = $response->json();
        if (! $response->successful() || ! isset($json['output'])) {
            return [
                'success' => false,
                'message' => $json['msg1'] ?? '한국투자증권 시세 조회 실패',
                'statusCode' => 502,
            ];
        }

        $data = $json['output'];

        return [
            'success' => true,
            'code' => $data['stck_shrn_iscd'] ?? $code,
            'price' => (int) ($data['stck_prpr'] ?? 0),
            'change' => (int) ($data['prdy_vrss'] ?? 0),
            'changeRate' => (float) ($data['prdy_ctrt'] ?? 0),
            'volume' => (int) ($data['acml_vol'] ?? 0),
            'turnoverAmount' => (int) ($data['acml_tr_pbmn'] ?? 0),
            'turnover' => (int) round(((int) ($data['acml_tr_pbmn'] ?? 0)) / 100_000_000),
            'open' => (int) ($data['stck_oprc'] ?? 0),
            'high' => (int) ($data['stck_hgpr'] ?? 0),
            'low' => (int) ($data['stck_lwpr'] ?? 0),
            'per' => (float) ($data['per'] ?? 0),
            'pbr' => (float) ($data['pbr'] ?? 0),
        ];
    }
}
