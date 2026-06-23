<?php

namespace Tests\Feature\Dashboard;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create(['name' => 'Makanan']);
        $this->today = now()->format('Y-m-d');
    }

    public function test_guest_cannot_access_dashboard_summary(): void
    {
        $this->getJson('/api/dashboard/summary')->assertUnauthorized();
    }

    public function test_guest_cannot_access_dashboard_expense_by_category(): void
    {
        $this->getJson('/api/dashboard/expense-by-category')->assertUnauthorized();
    }

    public function test_guest_cannot_access_dashboard_monthly_comparison(): void
    {
        $this->getJson('/api/dashboard/monthly-comparison')->assertUnauthorized();
    }

    public function test_guest_cannot_access_dashboard_cash_flow(): void
    {
        $this->getJson('/api/dashboard/cash-flow')->assertUnauthorized();
    }

    public function test_guest_cannot_access_dashboard_weekly_summary(): void
    {
        $this->getJson('/api/dashboard/weekly-summary')->assertUnauthorized();
    }

    public function test_summary_returns_correct_structure(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 1000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['total_income', 'total_expense', 'balance', 'month'],
            ]);

        $this->assertEquals(5000000, $response->json('data.total_income'));
        $this->assertEquals(1000000, $response->json('data.total_expense'));
        $this->assertEquals(4000000, $response->json('data.balance'));
    }

    public function test_summary_filters_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary?month=' . now()->subMonth()->format('Y-m'));

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total_income'));
    }

    public function test_expense_by_category_returns_correct_structure(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 200000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/expense-by-category');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['category_id', 'category_name', 'category_icon', 'total', 'percentage', 'transaction_count'],
                ],
            ]);

        $this->assertEquals('Makanan', $response->json('data.0.category_name'));
    }

    public function test_expense_by_category_filters_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 200000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 300000, 'transaction_date' => now()->subMonth()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/expense-by-category?month=' . now()->format('Y-m'));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(200000, $response->json('data.0.total'));
    }

    public function test_monthly_comparison_returns_correct_structure(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 2000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/monthly-comparison');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['month', 'income', 'expense'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_cash_flow_returns_correct_structure(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/cash-flow');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['daily', 'month'],
            ]);
    }

    public function test_cash_flow_filters_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 3000000, 'transaction_date' => now()->subMonth()->format('Y-m-d')]);

        $targetMonth = now()->subMonth()->format('Y-m');
        $response = $this->actingAs($this->user)->getJson('/api/dashboard/cash-flow?month=' . $targetMonth);

        $response->assertOk();
        $this->assertEquals($targetMonth, $response->json('data.month'));
    }

    public function test_weekly_summary_returns_correct_structure(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->expense()
            ->create(['amount' => 1000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/weekly-summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['week_start', 'week_end', 'income', 'expense', 'transaction_count'],
                ],
            ]);
    }

    public function test_weekly_summary_filters_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 5000000, 'transaction_date' => $this->today]);
        Transaction::factory()->for($this->user)->for($this->category)->income()
            ->create(['amount' => 3000000, 'transaction_date' => now()->subMonth()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/weekly-summary?month=' . now()->format('Y-m'));

        $response->assertOk();
        $totalIncome = array_sum(array_column($response->json('data'), 'income'));
        $this->assertEquals(5000000, $totalIncome);
    }

    public function test_user_cannot_see_other_users_data(): void
    {
        $otherUser = User::factory()->create();
        Transaction::factory()->for($otherUser)->for($this->category)->income()
            ->create(['amount' => 99000000, 'transaction_date' => $this->today]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total_income'));
    }
}
