<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getMonthlySummary(int $userId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $transactions = Transaction::forUser($userId)
            ->forMonth((int) $year, (int) $monthNum)
            ->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        return [
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'balance' => (float) ($totalIncome - $totalExpense),
            'month' => $month,
        ];
    }

    public function getExpenseByCategory(int $userId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $expenses = Transaction::forUser($userId)
            ->byType('expense')
            ->forMonth((int) $year, (int) $monthNum)
            ->with('category')
            ->get();

        $totalExpense = $expenses->sum('amount');

        if ($totalExpense == 0) {
            return [];
        }

        $grouped = $expenses->groupBy('category_id');

        $result = [];

        foreach ($grouped as $categoryId => $items) {
            $category = $items->first()->category;
            $total = $items->sum('amount');

            $result[] = [
                'category_id' => (int) $categoryId,
                'category_name' => $category->name,
                'category_icon' => $category->icon,
                'total' => (float) $total,
                'percentage' => round(($total / $totalExpense) * 100, 2),
                'transaction_count' => $items->count(),
            ];
        }

        usort($result, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }

    public function getMonthlyComparison(int $userId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $months[$monthKey] = ['month' => $monthKey, 'income' => 0, 'expense' => 0];
        }

        $firstMonth = now()->subMonths(5)->startOfMonth()->format('Y-m-d');
        $lastMonth = now()->endOfMonth()->format('Y-m-d');

        $transactions = Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$firstMonth, $lastMonth])
            ->get();

        foreach ($transactions as $t) {
            $monthKey = $t->transaction_date->format('Y-m');
            if (isset($months[$monthKey])) {
                if ($t->type === 'income') {
                    $months[$monthKey]['income'] += (float) $t->amount;
                } else {
                    $months[$monthKey]['expense'] += (float) $t->amount;
                }
            }
        }

        return array_values($months);
    }

    public function getCashFlow(int $userId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $startDate = "{$year}-{$monthNum}-01";
        $endDate = (new \DateTime("{$year}-{$monthNum}-01"))->modify('last day of this month')->format('Y-m-d');

        $previousMonthDate = (new \DateTime($startDate))->modify('-1 month');
        $previousMonthStart = $previousMonthDate->format('Y-m-01');
        $previousMonthEnd = $previousMonthDate->format('Y-m-t');

        $previousBalance = Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$previousMonthStart, $previousMonthEnd])
            ->get()
            ->reduce(function ($carry, $t) {
                return $carry + ($t->type === 'income' ? (float) $t->amount : -(float) $t->amount);
            }, 0);

        $transactions = Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        $dailyData = [];
        $runningBalance = $previousBalance;

        $dateRange = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            new \DateTime($endDate . ' 23:59:59')
        );

        $dailyTotals = [];
        foreach ($transactions as $t) {
            $day = $t->transaction_date->format('Y-m-d');
            if (! isset($dailyTotals[$day])) {
                $dailyTotals[$day] = ['income' => 0, 'expense' => 0];
            }
            if ($t->type === 'income') {
                $dailyTotals[$day]['income'] += (float) $t->amount;
            } else {
                $dailyTotals[$day]['expense'] += (float) $t->amount;
            }
        }

        foreach ($dateRange as $date) {
            $day = $date->format('Y-m-d');
            if (isset($dailyTotals[$day])) {
                $runningBalance += $dailyTotals[$day]['income'] - $dailyTotals[$day]['expense'];
                $dailyData[] = [
                    'date' => $day,
                    'income' => $dailyTotals[$day]['income'],
                    'expense' => $dailyTotals[$day]['expense'],
                    'balance' => $runningBalance,
                ];
            }
        }

        return [
            'daily' => $dailyData,
            'month' => $month,
        ];
    }

    public function getWeeklySummary(int $userId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $startDate = "{$year}-{$monthNum}-01";
        $endDate = (new \DateTime("{$year}-{$monthNum}-01"))->modify('last day of this month')->format('Y-m-d');

        $transactions = Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $grouped = $transactions->groupBy(function ($t) {
            return $t->transaction_date->startOfWeek()->format('Y-m-d');
        });

        $weeks = [];

        foreach ($grouped as $weekStart => $weekTransactions) {
            $weekEndDate = (new \DateTime($weekStart))->modify('+6 days');
            $weekEnd = $weekEndDate->format('Y-m-d');

            if ($weekEndDate->format('Y-m') !== $month) {
                $weekEnd = (new \DateTime("{$year}-{$monthNum}-01"))->modify('last day of this month')->format('Y-m-d');
            }

            $income = $weekTransactions->where('type', 'income')->sum('amount');
            $expense = $weekTransactions->where('type', 'expense')->sum('amount');

            $weeks[] = [
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'income' => (float) $income,
                'expense' => (float) $expense,
                'transaction_count' => $weekTransactions->count(),
            ];
        }

        return $weeks;
    }
}
