<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Services\Kis\KisStockService;
use Illuminate\Http\JsonResponse;
use Throwable;

class StockController extends Controller
{
    public function __construct(
        private KisStockService $kisStockService
    ) {}

    public function price(string $code): JsonResponse
    {
        try {
            $result = $this->kisStockService->getPrice($code);
            $statusCode = $result['statusCode'] ?? (($result['success'] ?? true) ? 200 : 502);
            unset($result['statusCode']);

            return response()->json($result, $statusCode);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => '주식 시세 정보를 불러오지 못했습니다.',
            ], 502);
        }
    }
}
