<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->dashboardService->getMonthlySummary($request->user()->id, $month);

        return response()->json(['data' => $data]);
    }

    public function expenseByCategory(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->dashboardService->getExpenseByCategory($request->user()->id, $month);

        return response()->json(['data' => $data]);
    }

    public function monthlyComparison(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getMonthlyComparison($request->user()->id);

        return response()->json(['data' => $data]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->dashboardService->getCashFlow($request->user()->id, $month);

        return response()->json(['data' => $data]);
    }

    public function weeklySummary(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->dashboardService->getWeeklySummary($request->user()->id, $month);

        return response()->json(['data' => $data]);
    }
}
