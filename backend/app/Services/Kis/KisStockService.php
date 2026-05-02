<?php

namespace App\Services\Kis;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class KisStockService
{
    public function getPrice(string $code)
    {
        $token = app(KisTokenService::class)->getToken();

        if (!$token) {
            throw new RuntimeException('한국투자증권 토큰 발급 실패');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'appkey' => config('kis.app_key'),
            'appsecret' => config('kis.app_secret'),
            'tr_id' => 'FHKST01010100',
        ])->get(config('kis.base_url') . '/uapi/domestic-stock/v1/quotations/inquire-price', [
            'FID_COND_MRKT_DIV_CODE' => 'J',
            'FID_INPUT_ISCD' => $code,
        ]);

        $json = $response->json();

        if (!$response->ok() || !isset($json['output'])) {
            return [
                'success' => false,
                'message' => $json['msg1'] ?? '한국투자증권 시세 조회 실패',
                'raw' => $json,
            ];
        }
        
        $data = $json['output'];

        return [
            'code' => $data['stck_shrn_iscd'] ?? $code,
            'price' => (int)($data['stck_prpr'] ?? 0),
            'change' => (int)($data['prdy_vrss'] ?? 0),
            'changeRate' => (float)($data['prdy_ctrt'] ?? 0),
            'volume' => (int)($data['acml_vol'] ?? 0),
            'open' => (int)($data['stck_oprc'] ?? 0),
            'high' => (int)($data['stck_hgpr'] ?? 0),
            'low' => (int)($data['stck_lwpr'] ?? 0),
            'per' => (float)($data['per'] ?? 0),
            'pbr' => (float)($data['pbr'] ?? 0),
        ];
    }
}