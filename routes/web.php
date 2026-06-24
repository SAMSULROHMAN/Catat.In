<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::view('/home', 'dashboard')->name('home');
    Route::view('/transactions', 'transactions.index')->name('transactions.index');
    Route::view('/budgets', 'budgets.index')->name('budgets.index');
    Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');

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

        Route::get('exports/excel', [ExportController::class, 'excel'])->name('exports.excel');
        Route::get('exports/csv', [ExportController::class, 'csv'])->name('exports.csv');

        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
            Route::get('expense-by-category', [DashboardController::class, 'expenseByCategory'])->name('dashboard.expense-by-category');
            Route::get('monthly-comparison', [DashboardController::class, 'monthlyComparison'])->name('dashboard.monthly-comparison');
            Route::get('cash-flow', [DashboardController::class, 'cashFlow'])->name('dashboard.cash-flow');
            Route::get('weekly-summary', [DashboardController::class, 'weeklySummary'])->name('dashboard.weekly-summary');
        });
    });
});
