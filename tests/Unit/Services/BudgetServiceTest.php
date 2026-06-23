<?php

namespace Tests\Unit\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $service;
    private User $user;
    private Category $category;
    private Budget $budget;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BudgetService;
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create(['name' => 'Makanan']);
        $this->budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => now()->format('Y-m'),
            ]);
        $this->today = now()->format('Y-m-d');
    }

    public function test_get_progress_returns_zero_when_no_expense(): void
    {
        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(0, $progress['percentage']);
        $this->assertEquals('green', $progress['color']);
        $this->assertEquals(0, $progress['total_expense']);
        $this->assertEquals(1000000, $progress['remaining']);
    }

    public function test_get_progress_returns_correct_percentage(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->count(3)
            ->create([
                'amount' => 100000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(30, $progress['percentage']);
        $this->assertEquals(300000, $progress['total_expense']);
        $this->assertEquals(700000, $progress['remaining']);
    }

    public function test_get_progress_color_green_below_70(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals('green', $progress['color']);
    }

    public function test_get_progress_color_yellow_70_to_90(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 800000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals('yellow', $progress['color']);
    }

    public function test_get_progress_color_red_above_90(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 950000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals('red', $progress['color']);
    }

    public function test_get_progress_caps_at_100_percent(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 2000000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(100, $progress['percentage']);
        $this->assertEquals(0, $progress['remaining']);
    }

    public function test_get_progress_only_counts_expense_transactions(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->income()
            ->create([
                'amount' => 5000000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(0, $progress['percentage']);
        $this->assertEquals(0, $progress['total_expense']);
    }

    public function test_get_progress_only_counts_same_category(): void
    {
        $otherCategory = Category::factory()->for($this->user)->create();

        Transaction::factory()
            ->for($this->user)
            ->for($otherCategory)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(0, $progress['percentage']);
        $this->assertEquals(0, $progress['total_expense']);
    }

    public function test_get_progress_only_counts_current_month(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => now()->subMonth()->format('Y-m-d'),
            ]);

        $progress = $this->service->getProgress($this->budget);

        $this->assertEquals(0, $progress['percentage']);
    }

    public function test_get_progress_returns_zero_when_limit_amount_is_zero(): void
    {
        $budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 0,
                'period_month' => now()->format('Y-m'),
            ]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => $this->today,
            ]);

        $progress = $this->service->getProgress($budget);

        $this->assertEquals(0, $progress['percentage']);
    }

    public function test_check_notification_returns_warning_at_80_percent(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 700000,
                'transaction_date' => $this->today,
            ]);

        $result = $this->service->checkNotification($this->budget, 100000);

        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
        $this->assertStringContainsString('80%', $result['message']);
    }

    public function test_check_notification_returns_danger_at_100_percent(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 900000,
                'transaction_date' => $this->today,
            ]);

        $result = $this->service->checkNotification($this->budget, 200000);

        $this->assertNotNull($result);
        $this->assertEquals('danger', $result['level']);
        $this->assertStringContainsString('melebihi', $result['message']);
    }

    public function test_check_notification_returns_null_below_80(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => $this->today,
            ]);

        $result = $this->service->checkNotification($this->budget, 100000);

        $this->assertNull($result);
    }

    public function test_check_notification_not_triggered_when_already_exceeded_before(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 1100000,
                'transaction_date' => $this->today,
            ]);

        $result = $this->service->checkNotification($this->budget, 200000);

        $this->assertNull($result);
    }

    public function test_check_notification_80_percent_does_not_trigger_when_already_above_80(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 850000,
                'transaction_date' => $this->today,
            ]);

        $result = $this->service->checkNotification($this->budget, 100000);

        $this->assertNull($result);
    }

    public function test_get_daily_remaining_returns_correct_structure(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 500000,
                'transaction_date' => $this->today,
            ]);

        $daily = $this->service->getDailyRemaining($this->budget);

        $this->assertNotNull($daily);
        $this->assertArrayHasKey('remaining', $daily);
        $this->assertArrayHasKey('remaining_days', $daily);
        $this->assertArrayHasKey('daily_budget', $daily);
        $this->assertEquals(500000, $daily['remaining']);
        $this->assertGreaterThan(0, $daily['remaining_days']);
        $this->assertGreaterThan(0, $daily['daily_budget']);
    }

    public function test_get_daily_remaining_returns_null_when_exceeded(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 1500000,
                'transaction_date' => $this->today,
            ]);

        $daily = $this->service->getDailyRemaining($this->budget);

        $this->assertNull($daily);
    }

    public function test_copy_from_previous_month_creates_new_budgets(): void
    {
        $otherCategory = Category::factory()->for($this->user)->create();
        $prevMonth = now()->subMonth()->format('Y-m');

        Budget::factory()
            ->for($this->user)
            ->for($otherCategory)
            ->create([
                'limit_amount' => 2000000,
                'period_month' => $prevMonth,
            ]);

        $copied = $this->service->copyFromPreviousMonth($this->user->id);

        $this->assertCount(1, $copied);

        $currentMonth = now()->format('Y-m');
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $otherCategory->id,
            'limit_amount' => 2000000,
            'period_month' => $currentMonth,
        ]);
    }

    public function test_copy_from_previous_month_skips_existing(): void
    {
        $prevMonth = now()->subMonth()->format('Y-m');
        $currentMonth = now()->format('Y-m');

        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 2000000,
                'period_month' => $prevMonth,
            ]);

        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 3000000,
                'period_month' => $currentMonth,
            ]);

        $copied = $this->service->copyFromPreviousMonth($this->user->id);

        $this->assertCount(0, $copied);
    }

    public function test_get_summary_returns_formatted_data(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 300000,
                'transaction_date' => $this->today,
            ]);

        $summary = $this->service->getSummary($this->user->id, now()->format('Y-m'));

        $this->assertCount(1, $summary);
        $this->assertEquals($this->budget->id, $summary[0]['id']);
        $this->assertEquals('Makanan', $summary[0]['category']['name']);
        $this->assertEquals(30, $summary[0]['percentage']);
        $this->assertEquals('green', $summary[0]['color']);
        $this->assertNotNull($summary[0]['daily_budget']);
    }

    public function test_get_summary_returns_empty_when_no_budgets(): void
    {
        $newUser = User::factory()->create();
        $summary = $this->service->getSummary($newUser->id, now()->format('Y-m'));

        $this->assertCount(0, $summary);
    }
}