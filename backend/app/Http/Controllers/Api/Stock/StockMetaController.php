<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;

class StockMetaController extends Controller
{
    // 1. 전체 종목 리스트
    public function index(Request $request)
    {
        $market = $request->query('market'); // kospi / kosdaq

        $query = Stock::query();

        if ($market) {
            $query->where('market', $market);
        }

        return response()->json(
            $query->select('code', 'name', 'market')
                  ->orderBy('name')
                  ->limit(1000)
                  ->get()
        );
    }

    // 2. 종목 검색
    public function search(Request $request)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json([]);
        }

        return response()->json(
            Stock::where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->limit(20)
                ->get(['code', 'name', 'market'])
        );
    }

    // 3. 종목 기본정보
    public function show($code)
    {
        $stock = Stock::where('code', $code)->first();

        if (!$stock) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($stock);
    }
}