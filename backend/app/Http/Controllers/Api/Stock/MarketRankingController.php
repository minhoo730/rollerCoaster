<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Services\MarketData\MarketRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketRankingController extends Controller
{
    public function __construct(
        private MarketRankingService $rankings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market' => ['sometimes', 'string', Rule::in($this->rankings->validMarkets())],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.config('market_data.rankings.max_limit', 50)],
            'type' => ['sometimes', 'string', Rule::in($this->rankings->validTypes())],
            'types' => ['sometimes', 'array'],
            'types.*' => ['string', Rule::in($this->rankings->validTypes())],
        ]);

        $types = $validated['types'] ?? [];
        if (isset($validated['type'])) {
            $types = [$validated['type']];
        }

        return response()->json([
            'success' => true,
            'message' => '랭킹 데이터를 조회했습니다.',
            'data' => $this->rankings->top(
                market: $validated['market'] ?? 'all',
                limit: (int) ($validated['limit'] ?? config('market_data.rankings.default_limit', 10)),
                types: $types,
            ),
        ]);
    }
}
