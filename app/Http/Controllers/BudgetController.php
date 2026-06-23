<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Budget::forUser($request->user()->id)->with('category');

        if ($request->filled('month')) {
            $query->forMonth($request->month);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'limit_amount' => ['required', 'numeric', 'min:0'],
            'period_month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $category = $request->user()->categories()->find($validated['category_id']);

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $existing = Budget::forUser($request->user()->id)
            ->forMonth($validated['period_month'])
            ->forCategory($validated['category_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Budget untuk kategori ini di bulan tersebut sudah ada.',
                'errors' => ['category_id' => ['Budget sudah ada.']],
            ], 422);
        }

        $budget = $request->user()->budgets()->create($validated);

        return response()->json($budget, 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $budget->load('category');
        $progress = $this->budgetService->getProgress($budget);
        $daily = $this->budgetService->getDailyRemaining($budget);

        return response()->json([
            'data' => $budget,
            'progress' => $progress,
            'daily_budget' => $daily,
        ]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $validated = $request->validate([
            'limit_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $budget->update($validated);

        return response()->json($budget);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $budget->delete();

        return response()->json(['message' => 'Budget berhasil dihapus.']);
    }

    public function summary(Request $request): JsonResponse
    {
        $month = $request->input('month', Budget::currentMonth());

        $summary = $this->budgetService->getSummary($request->user()->id, $month);

        return response()->json(['data' => $summary]);
    }

    public function copyPrevious(Request $request): JsonResponse
    {
        $copied = $this->budgetService->copyFromPreviousMonth($request->user()->id);

        return response()->json([
            'message' => count($copied) . ' budget berhasil disalin dari bulan sebelumnya.',
            'data' => $copied,
        ]);
    }
}