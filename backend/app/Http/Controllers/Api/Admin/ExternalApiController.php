<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stocks\Stock;
use App\Services\Kis\KisStockMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ExternalApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:120'],
            'market' => ['sometimes', Rule::in(['kospi', 'kosdaq', 'konex'])],
            'venue' => ['sometimes', Rule::in(['krx', 'nxt'])],
            'active' => ['sometimes', 'boolean'],
        ]);

        $stocks = Stock::query()
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($stockQuery) => $stockQuery
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($validated['market'] ?? null, fn ($query, string $market) => $query->where('market', $market))
            ->when($validated['venue'] ?? null, fn ($query, string $venue) => $query->where('venue', $venue))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('is_active', $validated['active']))
            ->orderBy('market')
            ->orderBy('code')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'data' => [
                'data' => $stocks->items(),
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                    'from' => $stocks->firstItem(),
                    'to' => $stocks->lastItem(),
                    'has_more_pages' => $stocks->hasMorePages(),
                ],
            ],
        ]);
    }

    public function update(Request $request, Stock $stock): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $stock->update($validated);

        return response()->json([
            'success' => true,
            'message' => '종목 정보를 저장했습니다.',
            'data' => $stock->fresh(),
        ]);
    }

    public function import(Request $request, KisStockMetaService $stockMeta): JsonResponse
    {
        $validated = $request->validate([
            'markets' => ['sometimes', 'array'],
            'markets.*' => ['string', Rule::in(['kospi', 'kosdaq', 'konex'])],
            'include_nxt' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $stockMeta->import(
                markets: $validated['markets'] ?? [],
                includeNxt: (bool) ($validated['include_nxt'] ?? false),
            );

            return response()->json($result, $result['success'] ? 200 : 207);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => '종목 마스터 동기화에 실패했습니다.',
            ], 500);
        }
    }
}
