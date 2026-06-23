<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;

class BudgetService
{
    public function getProgress(Budget $budget): array
    {
        $year = (int) substr($budget->period_month, 0, 4);
        $month = (int) substr($budget->period_month, 5, 2);

        $totalExpense = Transaction::forUser($budget->user_id)
            ->byCategory($budget->category_id)
            ->forMonth($year, $month)
            ->byType('expense')
            ->sum('amount');

        $percentage = $budget->limit_amount > 0
            ? min(round(($totalExpense / $budget->limit_amount) * 100, 2), 100)
            : 0;

        $color = match (true) {
            $percentage < 70 => 'green',
            $percentage <= 90 => 'yellow',
            default => 'red',
        };

        return [
            'budget_id' => $budget->id,
            'category_id' => $budget->category_id,
            'limit_amount' => (float) $budget->limit_amount,
            'total_expense' => (float) $totalExpense,
            'percentage' => $percentage,
            'color' => $color,
            'remaining' => max((float) $budget->limit_amount - $totalExpense, 0),
        ];
    }

    public function checkNotification(Budget $budget, float $newAmount): ?array
    {
        $year = (int) substr($budget->period_month, 0, 4);
        $month = (int) substr($budget->period_month, 5, 2);

        $currentTotal = Transaction::forUser($budget->user_id)
            ->byCategory($budget->category_id)
            ->forMonth($year, $month)
            ->byType('expense')
            ->sum('amount');

        $percentageBefore = $budget->limit_amount > 0
            ? ($currentTotal / $budget->limit_amount) * 100
            : 0;
        $percentageAfter = $budget->limit_amount > 0
            ? (($currentTotal + $newAmount) / $budget->limit_amount) * 100
            : 0;

        if ($percentageBefore < 80 && $percentageAfter >= 80) {
            return [
                'level' => 'warning',
                'message' => "Pengeluaran untuk kategori {$budget->category->name} telah mencapai 80% dari budget.",
                'percentage' => round($percentageAfter, 2),
            ];
        }

        if ($percentageBefore < 100 && $percentageAfter >= 100) {
            return [
                'level' => 'danger',
                'message' => "Pengeluaran untuk kategori {$budget->category->name} telah melebihi budget!",
                'percentage' => round($percentageAfter, 2),
            ];
        }

        return null;
    }

    public function getDailyRemaining(Budget $budget): ?array
    {
        $progress = $this->getProgress($budget);

        if ($progress['remaining'] <= 0) {
            return null;
        }

        $now = now();
        $endOfMonth = $now->copy()->endOfMonth();
        $remainingDays = max($now->diffInDays($endOfMonth) + 1, 1);

        $dailyBudget = round($progress['remaining'] / $remainingDays, 2);

        return [
            'remaining' => $progress['remaining'],
            'remaining_days' => $remainingDays,
            'daily_budget' => $dailyBudget,
        ];
    }

    public function copyFromPreviousMonth(int $userId): array
    {
        $previousMonth = Budget::previousMonth();
        $currentMonth = Budget::currentMonth();

        $previousBudgets = Budget::forUser($userId)
            ->forMonth($previousMonth)
            ->get();

        $copied = [];

        foreach ($previousBudgets as $budget) {
            $existing = Budget::forUser($userId)
                ->forMonth($currentMonth)
                ->forCategory($budget->category_id)
                ->first();

            if ($existing) {
                continue;
            }

            $new = Budget::create([
                'user_id' => $userId,
                'category_id' => $budget->category_id,
                'limit_amount' => $budget->limit_amount,
                'period_month' => $currentMonth,
            ]);

            $copied[] = $new;
        }

        return $copied;
    }

    public function getSummary(int $userId, string $periodMonth): array
    {
        $budgets = Budget::forUser($userId)->forMonth($periodMonth)->with('category')->get();

        $summary = [];

        foreach ($budgets as $budget) {
            $progress = $this->getProgress($budget);
            $daily = $this->getDailyRemaining($budget);

            $summary[] = [
                'id' => $budget->id,
                'category' => [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                    'icon' => $budget->category->icon,
                ],
                'limit_amount' => $progress['limit_amount'],
                'total_expense' => $progress['total_expense'],
                'percentage' => $progress['percentage'],
                'color' => $progress['color'],
                'remaining' => $progress['remaining'],
                'daily_budget' => $daily['daily_budget'] ?? null,
                'remaining_days' => $daily['remaining_days'] ?? null,
            ];
        }

        return $summary;
    }
}