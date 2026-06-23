<?php

namespace Tests\Unit\Budget;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category)->create();

        $this->assertInstanceOf(User::class, $budget->user);
        $this->assertEquals($user->id, $budget->user->id);
    }

    public function test_budget_belongs_to_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category)->create();

        $this->assertInstanceOf(Category::class, $budget->category);
        $this->assertEquals($category->id, $budget->category->id);
    }

    public function test_budget_has_correct_fillable_attributes(): void
    {
        $budget = new Budget;

        $this->assertEquals(
            ['user_id', 'category_id', 'limit_amount', 'period_month'],
            $budget->getFillable()
        );
    }

    public function test_budget_casts_limit_amount_to_decimal(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category)->create([
            'limit_amount' => 500000.75,
        ]);

        $this->assertEquals(500000.75, $budget->limit_amount);
    }

    public function test_user_has_many_budgets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Budget::factory()->count(3)->for($user)->for($category)->create();

        $this->assertCount(3, $user->budgets);
    }

    public function test_category_has_many_budgets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Budget::factory()->count(3)->for($user)->for($category)->create();

        $this->assertCount(3, $category->budgets);
    }

    public function test_scope_for_user_returns_only_user_budgets(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cat1 = Category::factory()->for($user1)->create();
        $cat2 = Category::factory()->for($user2)->create();

        Budget::factory()->count(2)->for($user1)->for($cat1)->create();
        Budget::factory()->count(3)->for($user2)->for($cat2)->create();

        $this->assertCount(2, Budget::forUser($user1->id)->get());
        $this->assertCount(3, Budget::forUser($user2->id)->get());
    }

    public function test_scope_for_month_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Budget::factory()->for($user)->for($category)->forMonth('2026-06')->create();
        Budget::factory()->for($user)->for($category)->forMonth('2026-06')->create();
        Budget::factory()->for($user)->for($category)->forMonth('2026-07')->create();

        $this->assertCount(2, Budget::forUser($user->id)->forMonth('2026-06')->get());
        $this->assertCount(1, Budget::forUser($user->id)->forMonth('2026-07')->get());
    }

    public function test_scope_for_category_filters_correctly(): void
    {
        $user = User::factory()->create();
        $cat1 = Category::factory()->for($user)->create();
        $cat2 = Category::factory()->for($user)->create();

        Budget::factory()->for($user)->for($cat1)->count(3)->create();
        Budget::factory()->for($user)->for($cat2)->count(2)->create();

        $this->assertCount(3, Budget::forUser($user->id)->forCategory($cat1->id)->get());
        $this->assertCount(2, Budget::forUser($user->id)->forCategory($cat2->id)->get());
    }

    public function test_current_month_returns_correct_format(): void
    {
        $expected = now()->format('Y-m');
        $this->assertEquals($expected, Budget::currentMonth());
    }

    public function test_previous_month_returns_correct_format(): void
    {
        $expected = now()->subMonth()->format('Y-m');
        $this->assertEquals($expected, Budget::previousMonth());
    }
}