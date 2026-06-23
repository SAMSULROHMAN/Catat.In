<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::forUser($request->user()->id)->latestFirst();

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
            $query->forMonth((int) $year, (int) $month);
        }

        $transactions = $query->with('category')->get();

        return response()->json(['data' => $transactions]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
        ]);

        $category = $request->user()->categories()->find($validated['category_id']);

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $notification = null;

        if ($validated['type'] === 'expense') {
            $budget = Budget::forUser($request->user()->id)
                ->forMonth(Budget::currentMonth())
                ->forCategory($validated['category_id'])
                ->first();

            if ($budget) {
                $budget->load('category');
                $notification = app(BudgetService::class)->checkNotification(
                    $budget,
                    (float) $validated['amount']
                );
            }
        }

        $transaction = $request->user()->transactions()->create($validated);

        if ($notification) {
            return response()->json([
                'data' => $transaction,
                'notification' => $notification,
            ], 201);
        }

        return response()->json($transaction, 201);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json($transaction);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
        ]);

        $category = $request->user()->categories()->find($validated['category_id']);

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $transaction->update($validated);

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaksi berhasil dihapus.']);
    }

    public function duplicate(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $duplicate = $transaction->replicate();
        $duplicate->note = $transaction->note
            ? $transaction->note . ' (copy)'
            : null;
        $duplicate->save();

        return response()->json($duplicate, 201);
    }
}
