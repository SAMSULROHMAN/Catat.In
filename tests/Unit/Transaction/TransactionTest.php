<?php

namespace Tests\Unit\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($category)->create();

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
    }

    public function test_transaction_belongs_to_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($category)->create();

        $this->assertInstanceOf(Category::class, $transaction->category);
        $this->assertEquals($category->id, $transaction->category->id);
    }

    public function test_transaction_has_correct_fillable_attributes(): void
    {
        $transaction = new Transaction;

        $this->assertEquals(
            ['user_id', 'category_id', 'type', 'amount', 'note', 'transaction_date'],
            $transaction->getFillable()
        );
    }

    public function test_transaction_default_type_is_null(): void
    {
        $transaction = new Transaction;
        $this->assertNull($transaction->type);
    }

    public function test_transaction_casts_amount_to_decimal(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($category)->create([
            'amount' => 50000.50,
        ]);

        $this->assertEquals(50000.50, $transaction->amount);
    }

    public function test_transaction_casts_transaction_date_to_date(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($category)->create([
            'transaction_date' => '2026-06-23',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->transaction_date);
        $this->assertEquals('2026-06-23', $transaction->transaction_date->format('Y-m-d'));
    }

    public function test_user_has_many_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Transaction::factory()->count(3)->for($user)->for($category)->create();

        $this->assertCount(3, $user->transactions);
    }

    public function test_scope_for_user_returns_only_user_transactions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cat1 = Category::factory()->for($user1)->create();
        $cat2 = Category::factory()->for($user2)->create();

        Transaction::factory()->count(2)->for($user1)->for($cat1)->create();
        Transaction::factory()->count(3)->for($user2)->for($cat2)->create();

        $this->assertCount(2, Transaction::forUser($user1->id)->get());
        $this->assertCount(3, Transaction::forUser($user2->id)->get());
    }

    public function test_scope_by_type_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($category)->count(3)->create(['type' => 'income']);
        Transaction::factory()->for($user)->for($category)->count(2)->create(['type' => 'expense']);

        $this->assertCount(3, Transaction::byType('income')->get());
        $this->assertCount(2, Transaction::byType('expense')->get());
    }

    public function test_scope_for_month_filters_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($category)->create(['transaction_date' => '2026-06-01']);
        Transaction::factory()->for($user)->for($category)->create(['transaction_date' => '2026-06-15']);
        Transaction::factory()->for($user)->for($category)->create(['transaction_date' => '2026-07-01']);

        $this->assertCount(2, Transaction::forMonth(2026, 6)->get());
        $this->assertCount(1, Transaction::forMonth(2026, 7)->get());
    }

    public function test_scope_by_category_filters_correctly(): void
    {
        $user = User::factory()->create();
        $cat1 = Category::factory()->for($user)->create();
        $cat2 = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($cat1)->count(3)->create();
        Transaction::factory()->for($user)->for($cat2)->count(2)->create();

        $this->assertCount(3, Transaction::byCategory($cat1->id)->get());
        $this->assertCount(2, Transaction::byCategory($cat2->id)->get());
    }

    public function test_transaction_scope_latest_first(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $old = Transaction::factory()->for($user)->for($category)->create([
            'transaction_date' => '2026-01-01',
        ]);
        $new = Transaction::factory()->for($user)->for($category)->create([
            'transaction_date' => '2026-06-23',
        ]);

        $transactions = Transaction::forUser($user->id)->latestFirst()->get();

        $this->assertEquals($new->id, $transactions->first()->id);
        $this->assertEquals($old->id, $transactions->last()->id);
    }

    public function test_category_has_many_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Transaction::factory()->count(3)->for($user)->for($category)->create();

        $this->assertCount(3, $category->transactions);
    }
}
