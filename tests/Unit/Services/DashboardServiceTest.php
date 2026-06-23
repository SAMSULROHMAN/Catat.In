<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;
    private Category $categoryFood;
    private Category $categoryTransport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService;
        $this->user = User::factory()->create();
        $this->categoryFood = Category::factory()->for($this->user)->create(['name' => 'Makanan']);
        $this->categoryTransport = Category::factory()->for($this->user)->create(['name' => 'Transport']);
    }

    public function test_get_monthly_summary_returns_zero_when_no_transactions(): void
    {
        $summary = $this->service->getMonthlySummary($this->user->id);

        $this->assertEquals(0, $summary['total_income']);
        $this->assertEquals(0, $summary['total_expense']);
        $this->assertEquals(0, $summary['balance']);
        $this->assertEquals(now()->format('Y-m'), $summary['month']);
    }

    public function test_get_monthly_summary_calculates_correctly(): void
    {
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->expense()
            ->create(['amount' => 1000000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 500000, 'transaction_date' => $today]);

        $summary = $this->service->getMonthlySummary($this->user->id);

        $this->assertEquals(5000000, $summary['total_income']);
        $this->assertEquals(1500000, $summary['total_expense']);
        $this->assertEquals(3500000, $summary['balance']);
    }

    public function test_get_monthly_summary_filters_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 5000000, 'transaction_date' => now()->format('Y-m-d')]);

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 3000000, 'transaction_date' => now()->subMonth()->format('Y-m-d')]);

        $summary = $this->service->getMonthlySummary($this->user->id, now()->subMonth()->format('Y-m'));

        $this->assertEquals(3000000, $summary['total_income']);
        $this->assertEquals(0, $summary['total_expense']);
    }

    public function test_get_monthly_summary_only_counts_own_user(): void
    {
        $otherUser = User::factory()->create();
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $today]);
        Transaction::factory()->for($otherUser)->for($this->categoryFood)->income()
            ->create(['amount' => 9000000, 'transaction_date' => $today]);

        $summary = $this->service->getMonthlySummary($this->user->id);

        $this->assertEquals(5000000, $summary['total_income']);
    }

    public function test_get_expense_by_category_returns_empty_when_no_expenses(): void
    {
        $result = $this->service->getExpenseByCategory($this->user->id);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_expense_by_category_groupsCorrectly(): void
    {
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 100000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 200000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->expense()
            ->create(['amount' => 300000, 'transaction_date' => $today]);

        $result = $this->service->getExpenseByCategory($this->user->id);

        $this->assertCount(2, $result);

        $foodEntry = collect($result)->firstWhere('category_id', $this->categoryFood->id);
        $this->assertNotNull($foodEntry);
        $this->assertEquals(300000, $foodEntry['total']);
        $this->assertEquals(2, $foodEntry['transaction_count']);

        $transportEntry = collect($result)->firstWhere('category_id', $this->categoryTransport->id);
        $this->assertNotNull($transportEntry);
        $this->assertEquals(300000, $transportEntry['total']);
    }

    public function test_get_expense_by_category_includes_percentage(): void
    {
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 300000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->expense()
            ->create(['amount' => 700000, 'transaction_date' => $today]);

        $result = $this->service->getExpenseByCategory($this->user->id);

        $foodEntry = collect($result)->firstWhere('category_id', $this->categoryFood->id);
        $this->assertEquals(30, $foodEntry['percentage']);

        $transportEntry = collect($result)->firstWhere('category_id', $this->categoryTransport->id);
        $this->assertEquals(70, $transportEntry['percentage']);
    }

    public function test_get_expense_by_category_filters_by_month(): void
    {
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 100000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 200000, 'transaction_date' => now()->subMonth()->format('Y-m-d')]);

        $result = $this->service->getExpenseByCategory($this->user->id, now()->format('Y-m'));

        $this->assertCount(1, $result);
        $this->assertEquals(100000, $result[0]['total']);
    }

    public function test_get_expense_by_category_only_counts_expenses(): void
    {
        $today = now()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $today]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 100000, 'transaction_date' => $today]);

        $result = $this->service->getExpenseByCategory($this->user->id);

        $this->assertCount(1, $result);
        $this->assertEquals(100000, $result[0]['total']);
    }

    public function test_get_monthly_comparison_returns_6_months_with_zeros_when_no_data(): void
    {
        $result = $this->service->getMonthlyComparison($this->user->id);

        $this->assertIsArray($result);
        $this->assertCount(6, $result);
        $this->assertEquals(0, $result[0]['income']);
        $this->assertEquals(0, $result[0]['expense']);
    }

    public function test_get_monthly_comparison_returns_6_months(): void
    {
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $date = now()->subMonths($i)->format('Y-m-15');

            Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
                ->create(['amount' => 1000000, 'transaction_date' => $date]);
            Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
                ->create(['amount' => 500000, 'transaction_date' => $date]);
        }

        $result = $this->service->getMonthlyComparison($this->user->id);

        $this->assertCount(6, $result);
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('income', $result[0]);
        $this->assertArrayHasKey('expense', $result[0]);
    }

    public function test_get_monthly_comparison_sumsCorrectly(): void
    {
        $currentMonth = now()->format('Y-m-15');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 2000000, 'transaction_date' => $currentMonth]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->income()
            ->create(['amount' => 3000000, 'transaction_date' => $currentMonth]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 800000, 'transaction_date' => $currentMonth]);

        $result = $this->service->getMonthlyComparison($this->user->id);

        $currentEntry = collect($result)->firstWhere('month', now()->format('Y-m'));
        $this->assertNotNull($currentEntry);
        $this->assertEquals(5000000, $currentEntry['income']);
        $this->assertEquals(800000, $currentEntry['expense']);
    }

    public function test_get_cash_flow_returns_empty_when_no_transactions(): void
    {
        $result = $this->service->getCashFlow($this->user->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('daily', $result);
        $this->assertArrayHasKey('month', $result);
        $this->assertCount(0, $result['daily']);
    }

    public function test_get_cash_flow_calculates_daily_balance(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $yesterday]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 1000000, 'transaction_date' => $today]);

        $result = $this->service->getCashFlow($this->user->id);

        $this->assertCount(2, $result['daily']);

        $yesterdayEntry = collect($result['daily'])->firstWhere('date', $yesterday);
        $this->assertNotNull($yesterdayEntry);
        $this->assertEquals(5000000, $yesterdayEntry['balance']);

        $todayEntry = collect($result['daily'])->firstWhere('date', $today);
        $this->assertNotNull($todayEntry);
        $this->assertEquals(4000000, $todayEntry['balance']);
    }

    public function test_get_cash_flow_filters_by_month(): void
    {
        $lastMonthDate = now()->subMonth()->format('Y-m-15');
        $thisMonthDate = now()->format('Y-m-15');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 1000000, 'transaction_date' => $lastMonthDate]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 500000, 'transaction_date' => $thisMonthDate]);

        $result = $this->service->getCashFlow($this->user->id, now()->subMonth()->format('Y-m'));

        $this->assertCount(1, $result['daily']);
        $this->assertEquals(now()->subMonth()->format('Y-m'), $result['month']);
    }

    public function test_get_weekly_summary_returns_empty_when_no_transactions(): void
    {
        $result = $this->service->getWeeklySummary($this->user->id);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_weekly_summary_groups_by_week(): void
    {
        $startOfWeek = now()->startOfWeek()->format('Y-m-d');
        $midWeek = now()->startOfWeek()->addDays(2)->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 2000000, 'transaction_date' => $startOfWeek]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 500000, 'transaction_date' => $startOfWeek]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->expense()
            ->create(['amount' => 300000, 'transaction_date' => $midWeek]);

        $result = $this->service->getWeeklySummary($this->user->id);

        $this->assertGreaterThanOrEqual(1, count($result));

        $weekEntry = $result[0];
        $this->assertArrayHasKey('week_start', $weekEntry);
        $this->assertArrayHasKey('week_end', $weekEntry);
        $this->assertArrayHasKey('income', $weekEntry);
        $this->assertArrayHasKey('expense', $weekEntry);
        $this->assertArrayHasKey('transaction_count', $weekEntry);
    }

    public function test_get_weekly_summary_sumsCorrectly(): void
    {
        $startOfWeek = now()->startOfWeek()->format('Y-m-d');
        $midWeek = now()->startOfWeek()->addDays(1)->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 2000000, 'transaction_date' => $startOfWeek]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->expense()
            ->create(['amount' => 500000, 'transaction_date' => $startOfWeek]);
        Transaction::factory()->for($this->user)->for($this->categoryTransport)->expense()
            ->create(['amount' => 300000, 'transaction_date' => $midWeek]);

        $result = $this->service->getWeeklySummary($this->user->id);

        $firstWeek = $result[0];
        $this->assertEquals(2000000, $firstWeek['income']);
        $this->assertEquals(800000, $firstWeek['expense']);
        $this->assertEquals(3, $firstWeek['transaction_count']);
    }

    public function test_get_weekly_summary_filters_by_month(): void
    {
        $lastMonthDate = now()->subMonth()->startOfWeek()->format('Y-m-d');
        $thisMonthDate = now()->startOfWeek()->format('Y-m-d');

        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 1000000, 'transaction_date' => $lastMonthDate]);
        Transaction::factory()->for($this->user)->for($this->categoryFood)->income()
            ->create(['amount' => 2000000, 'transaction_date' => $thisMonthDate]);

        $result = $this->service->getWeeklySummary($this->user->id, now()->format('Y-m'));

        $totalIncome = array_sum(array_column($result, 'income'));
        $this->assertEquals(2000000, $totalIncome);
    }
}
