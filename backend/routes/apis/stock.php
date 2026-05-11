<?php

use App\Http\Controllers\Api\Stock\MarketRankingController;
use App\Http\Controllers\Api\Stock\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/rankings', [MarketRankingController::class, 'index'])
    ->name('stocks.rankings');

Route::get('/{code}/price', [StockController::class, 'price'])
    ->where('code', '[0-9]{6}')
    ->name('stocks.price');
