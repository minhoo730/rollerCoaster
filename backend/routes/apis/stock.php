<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Stock\StockController;

Route::get('/{code}/price', [StockController::class, 'price'])
    ->name('stocks.price');