<?php

use App\Http\Controllers\Api\Stock\StockMetaController;
use Illuminate\Support\Facades\Route;

Route::prefix('stocks')->middleware('throttle:60,1')->group(function () {
    Route::post('/meta/import-kis', [StockMetaController::class, 'importKis'])
        ->middleware(['auth:sanctum', 'admin'])
        ->name('stocks.meta.import-kis');

    Route::get('/', [StockMetaController::class, 'index'])->name('stocks.meta.index');
    Route::get('/search', [StockMetaController::class, 'search'])->name('stocks.meta.search');
    Route::get('/{code}', [StockMetaController::class, 'show'])
        ->where('code', '[0-9]{6}')
        ->name('stocks.meta.show');
});
