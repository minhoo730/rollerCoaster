<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockMetaController;

Route::prefix('stocks')->group(function () {
    Route::get('/', [StockMetaController::class, 'index']);          // 전체 목록
    Route::get('/search', [StockMetaController::class, 'search']);   // 검색
    Route::get('/{code}', [StockMetaController::class, 'show']);     // 기본정보
});