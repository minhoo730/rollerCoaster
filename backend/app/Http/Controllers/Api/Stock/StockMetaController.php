<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stocks\Stock;
use App\Services\Kis\KisStockMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class StockMetaController extends Controller
{
    public function __construct(
        private KisStockMetaService $kisStockMetaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Stock::query()->where('is_active', true);

        if ($market = $request->query('market')) {
            $query->where('market', $market);
        }

        if ($venue = $request->query('venue')) {
            $query->where('venue', $venue);
        }

        return response()->json(
            $query->select('code', 'name', 'market', 'venue', 'sector', 'is_active')
                ->orderBy('name')
                ->limit(1000)
                ->get()
        );
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json([]);
        }

        return response()->json(
            Stock::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                ->limit(20)
                ->get(['code', 'name', 'market', 'venue', 'sector'])
        );
    }

    public function show(string $code): JsonResponse
    {
        $stock = Stock::query()
            ->where('code', $code)
            ->where('venue', request()->query('venue', 'krx'))
            ->first();

        if (! $stock) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($stock);
    }

    public function importKis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'markets' => ['sometimes', 'array'],
            'markets.*' => ['string', Rule::in(['kospi', 'kosdaq', 'konex'])],
            'include_nxt' => ['sometimes', 'boolean'],
            'chunk_size' => ['sometimes', 'integer', 'min:1', 'max:5000'],
            'use_upsert' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->kisStockMetaService->import(
                markets: $validated['markets'] ?? [],
                includeNxt: (bool) ($validated['include_nxt'] ?? false),
                chunkSize: $validated['chunk_size'] ?? null,
                useUpsert: $validated['use_upsert'] ?? null,
            );

            return response()->json($result, $result['success'] ? 200 : 207);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => '종목 마스터 수집에 실패했습니다.',
            ], 500);
        }
    }
}
