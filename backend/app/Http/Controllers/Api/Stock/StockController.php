<?php

namespace App\Http\Controllers\Api\Stock;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Kis\KisStockService;

class StockController extends Controller
{
    public function __construct(
        private KisStockService $kisStockService
    ) {}

    public function price($code)
    {
        return response()->json(
            $this->kisStockService->getPrice($code)
        );
    }
}