<?php

namespace App\Services\Kis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class KisTokenService
{
    public function getToken()
    {
        return Cache::remember('kis_access_token', 60 * 60 * 23, function () {
            if (! config('kis.app_key') || ! config('kis.app_secret') || ! config('kis.base_url')) {
                return null;
            }

            $response = Http::post(
                config('kis.base_url') . '/oauth2/tokenP',
                [
                    'grant_type' => 'client_credentials',
                    'appkey' => config('kis.app_key'),
                    'appsecret' => config('kis.app_secret'),
                ]
            );

            if (!$response->ok()) {
                return null;
            }

            return $response->json()['access_token'] ?? null;
        });
    }
}
