<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::view('/home', 'dashboard')->name('home');
    Route::view('/transactions', 'transactions.index')->name('transactions.index');
    Route::view('/budgets', 'budgets.index')->name('budgets.index');

    Route::prefix('api')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::post('categories/{category}/favorite', [CategoryController::class, 'toggleFavorite'])
            ->name('categories.favorite');

        Route::apiResource('transactions', TransactionController::class);
        Route::post('transactions/{transaction}/duplicate', [TransactionController::class, 'duplicate'])
            ->name('transactions.duplicate');

        Route::get('budgets/summary', [BudgetController::class, 'summary'])->name('budgets.summary');
        Route::post('budgets/copy-previous', [BudgetController::class, 'copyPrevious'])->name('budgets.copy-previous');
        Route::apiResource('budgets', BudgetController::class);
    });
});
