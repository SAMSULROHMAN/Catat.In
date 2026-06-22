<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::post('categories/{category}/favorite', [CategoryController::class, 'toggleFavorite'])
        ->name('categories.favorite');
});
