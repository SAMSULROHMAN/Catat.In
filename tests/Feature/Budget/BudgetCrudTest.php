<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private string $currentMonth;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create(['name' => 'Makanan']);
        $this->currentMonth = now()->format('Y-m');
        $this->today = now()->format('Y-m-d');
    }

    public function test_user_can_list_budgets(): void
    {
        Budget::factory()->count(3)->for($this->user)->for($this->category)->create();

        $response = $this->actingAs($this->user)->getJson('/api/budgets');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_filter_budgets_by_month(): void
    {
        Budget::factory()->for($this->user)->for($this->category)->forMonth('2026-06')->create();
        Budget::factory()->for($this->user)->for($this->category)->forMonth('2026-07')->create();

        $response = $this->actingAs($this->user)->getJson('/api/budgets?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_budget(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'category_id' => $this->category->id,
            'limit_amount' => 1000000,
            'period_month' => $this->currentMonth,
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'limit_amount' => '1000000.00',
                'period_month' => $this->currentMonth,
            ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'limit_amount' => 1000000.00,
            'period_month' => $this->currentMonth,
        ]);
    }

    public function test_category_id_is_required(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'limit_amount' => 1000000,
            'period_month' => $this->currentMonth,
        ]);

        $response->assertInvalid(['category_id']);
    }

    public function test_limit_amount_is_required(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'category_id' => $this->category->id,
            'period_month' => $this->currentMonth,
        ]);

        $response->assertInvalid(['limit_amount']);
    }

    public function test_period_month_format_is_validated(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'category_id' => $this->category->id,
            'limit_amount' => 1000000,
            'period_month' => 'invalid-date',
        ]);

        $response->assertInvalid(['period_month']);
    }

    public function test_cannot_create_duplicate_budget_for_same_category_and_month(): void
    {
        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'period_month' => $this->currentMonth,
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'category_id' => $this->category->id,
            'limit_amount' => 2000000,
            'period_month' => $this->currentMonth,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_cannot_create_budget_for_category_of_other_user(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->postJson('/api/budgets', [
            'category_id' => $otherCategory->id,
            'limit_amount' => 1000000,
            'period_month' => $this->currentMonth,
        ]);

        $response->assertNotFound();
    }

    public function test_user_can_show_budget(): void
    {
        $budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)->getJson("/api/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'progress' => ['budget_id', 'percentage', 'color', 'total_expense', 'remaining'],
                'daily_budget',
            ]);
    }

    public function test_user_cannot_access_other_users_budget(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $budget = Budget::factory()->for($otherUser)->for($otherCategory)->create();

        $this->actingAs($this->user)->getJson("/api/budgets/{$budget->id}")
            ->assertNotFound();

        $this->actingAs($this->user)->putJson("/api/budgets/{$budget->id}", [
            'limit_amount' => 2000000,
        ])->assertNotFound();

        $this->actingAs($this->user)->deleteJson("/api/budgets/{$budget->id}")
            ->assertNotFound();
    }

    public function test_user_can_update_budget(): void
    {
        $budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create(['limit_amount' => 500000]);

        $response = $this->actingAs($this->user)->putJson("/api/budgets/{$budget->id}", [
            'limit_amount' => 2000000,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['limit_amount' => '2000000.00']);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'limit_amount' => 2000000.00,
        ]);
    }

    public function test_user_can_delete_budget(): void
    {
        $budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/budgets/{$budget->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_guest_cannot_access_budgets(): void
    {
        $this->getJson('/api/budgets')->assertUnauthorized();
        $this->postJson('/api/budgets', [
            'category_id' => 1, 'limit_amount' => 1000000, 'period_month' => $this->currentMonth,
        ])->assertUnauthorized();
        $this->getJson('/api/budgets/1')->assertUnauthorized();
        $this->putJson('/api/budgets/1', ['limit_amount' => 2000000])->assertUnauthorized();
        $this->deleteJson('/api/budgets/1')->assertUnauthorized();
    }

    public function test_budget_summary_returns_progress(): void
    {
        $budget = Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => $this->currentMonth,
            ]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->count(3)
            ->create([
                'amount' => 100000,
                'transaction_date' => $this->today,
            ]);

        $response = $this->actingAs($this->user)->getJson('/api/budgets/summary');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals(30, $data[0]['percentage']);
        $this->assertEquals('green', $data[0]['color']);
        $this->assertEquals('Makanan', $data[0]['category']['name']);
    }

    public function test_budget_summary_filters_by_month(): void
    {
        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => '2026-06',
            ]);

        $response = $this->actingAs($this->user)->getJson('/api/budgets/summary?month=2026-06');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));

        $response = $this->actingAs($this->user)->getJson('/api/budgets/summary?month=2026-07');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_copy_previous_month_budgets(): void
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

        $response = $this->actingAs($this->user)->postJson('/api/budgets/copy-previous');

        $response->assertOk()
            ->assertJsonFragment(['message' => '1 budget berhasil disalin dari bulan sebelumnya.']);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $otherCategory->id,
            'limit_amount' => 2000000,
            'period_month' => $this->currentMonth,
        ]);
    }

    public function test_copy_previous_month_skips_existing(): void
    {
        $prevMonth = now()->subMonth()->format('Y-m');

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
                'period_month' => $this->currentMonth,
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/budgets/copy-previous');

        $response->assertOk()
            ->assertJsonFragment(['message' => '0 budget berhasil disalin dari bulan sebelumnya.']);
    }

    public function test_transaction_creation_triggers_warning_notification_at_80_percent(): void
    {
        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => $this->currentMonth,
            ]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 700000,
                'transaction_date' => $this->today,
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100000,
            'note' => 'Jajan',
            'transaction_date' => $this->today,
        ]);

        $response->assertCreated();
        $notification = $response->json('notification');

        $this->assertNotNull($notification);
        $this->assertEquals('warning', $notification['level']);
        $this->assertStringContainsString('80%', $notification['message']);
    }

    public function test_transaction_creation_triggers_danger_notification_at_100_percent(): void
    {
        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => $this->currentMonth,
            ]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->category)
            ->expense()
            ->create([
                'amount' => 900000,
                'transaction_date' => $this->today,
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 200000,
            'note' => 'Belanja',
            'transaction_date' => $this->today,
        ]);

        $response->assertCreated();
        $notification = $response->json('notification');

        $this->assertNotNull($notification);
        $this->assertEquals('danger', $notification['level']);
        $this->assertStringContainsString('melebihi', $notification['message']);
    }

    public function test_transaction_creation_no_notification_for_income(): void
    {
        Budget::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'limit_amount' => 1000000,
                'period_month' => $this->currentMonth,
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 5000000,
            'note' => 'Gaji',
            'transaction_date' => $this->today,
        ]);

        $response->assertCreated();
        $this->assertArrayNotHasKey('notification', $response->json());
    }

    public function test_transaction_creation_no_notification_without_budget(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500000,
            'note' => 'Belanja',
            'transaction_date' => $this->today,
        ]);

        $response->assertCreated();
        $this->assertArrayNotHasKey('notification', $response->json());
    }
}