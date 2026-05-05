<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Stock\StockController;

Route::get('/{code}/price', [StockController::class, 'price'])
    ->where('code', '[0-9]{6}')
    ->name('stocks.price');
