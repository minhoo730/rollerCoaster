<?php

namespace App\Services\Kis;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KisTokenService
{
    public function getToken(): ?string
    {
        $appKey = (string) config('kis.app_key');
        $appSecret = (string) config('kis.app_secret');
        $baseUrl = rtrim((string) config('kis.base_url'), '/');

        if ($appKey === '' || $appSecret === '' || $baseUrl === '') {
            return null;
        }

        $cacheKey = 'kis_access_token:'.hash('sha256', $baseUrl.'|'.$appKey);

        return Cache::remember($cacheKey, now()->addHours(23), function () use ($appKey, $appSecret, $baseUrl) {
            $response = Http::acceptJson()
                ->timeout((int) config('kis.timeout', 10))
                ->retry(2, 300)
                ->post($baseUrl.'/oauth2/tokenP', [
                    'grant_type' => 'client_credentials',
                    'appkey' => $appKey,
                    'appsecret' => $appSecret,
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('access_token');
        });
    }
}
