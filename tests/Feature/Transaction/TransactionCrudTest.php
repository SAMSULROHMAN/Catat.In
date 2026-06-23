<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create();
    }

    public function test_user_can_list_transactions(): void
    {
        Transaction::factory()->count(3)->for($this->user)->for($this->category)->create();

        $response = $this->actingAs($this->user)->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_transaction(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 50000,
            'note' => 'Beli nasi goreng',
            'transaction_date' => '2026-06-23',
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'type' => 'expense',
                'amount' => '50000.00',
                'note' => 'Beli nasi goreng',
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 50000.00,
        ]);
    }

    public function test_amount_is_required(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'expense',
        ]);

        $response->assertInvalid(['amount']);
    }

    public function test_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $this->category->id,
            'type' => 'invalid_type',
            'amount' => 50000,
        ]);

        $response->assertInvalid(['type']);
    }

    public function test_user_can_show_transaction(): void
    {
        $transaction = Transaction::factory()->for($this->user)->for($this->category)->create();

        $response = $this->actingAs($this->user)->getJson("/api/transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $transaction->id]);
    }

    public function test_user_can_update_transaction(): void
    {
        $transaction = Transaction::factory()->for($this->user)->for($this->category)->create([
            'note' => 'Old note',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/transactions/{$transaction->id}", [
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100000,
            'note' => 'Updated note',
            'transaction_date' => '2026-06-23',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['note' => 'Updated note']);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'note' => 'Updated note',
            'amount' => 100000.00,
        ]);
    }

    public function test_user_can_delete_transaction(): void
    {
        $transaction = Transaction::factory()->for($this->user)->for($this->category)->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_access_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()->for($otherUser)->for($otherCategory)->create();

        $this->actingAs($this->user)->getJson("/api/transactions/{$transaction->id}")
            ->assertNotFound();

        $this->actingAs($this->user)->putJson("/api/transactions/{$transaction->id}", [
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1000,
            'transaction_date' => '2026-06-23',
        ])->assertNotFound();

        $this->actingAs($this->user)->deleteJson("/api/transactions/{$transaction->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_access_transactions(): void
    {
        $this->getJson('/api/transactions')->assertUnauthorized();
        $this->postJson('/api/transactions', [
            'category_id' => 1, 'type' => 'expense', 'amount' => 1000, 'transaction_date' => '2026-06-23',
        ])->assertUnauthorized();
    }

    public function test_user_can_filter_transactions_by_type(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->count(3)->create(['type' => 'income']);
        Transaction::factory()->for($this->user)->for($this->category)->count(2)->create(['type' => 'expense']);

        $response = $this->actingAs($this->user)->getJson('/api/transactions?type=income');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_filter_transactions_by_category(): void
    {
        $cat1 = Category::factory()->for($this->user)->create();
        $cat2 = Category::factory()->for($this->user)->create();

        Transaction::factory()->for($this->user)->for($cat1)->count(3)->create();
        Transaction::factory()->for($this->user)->for($cat2)->count(2)->create();

        $response = $this->actingAs($this->user)->getJson('/api/transactions?category_id=' . $cat1->id);

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_filter_transactions_by_month(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->create(['transaction_date' => '2026-06-01']);
        Transaction::factory()->for($this->user)->for($this->category)->create(['transaction_date' => '2026-06-15']);
        Transaction::factory()->for($this->user)->for($this->category)->create(['transaction_date' => '2026-07-01']);

        $response = $this->actingAs($this->user)->getJson('/api/transactions?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_duplicate_transaction(): void
    {
        $transaction = Transaction::factory()->for($this->user)->for($this->category)->create([
            'type' => 'expense',
            'amount' => 75000,
            'note' => 'Makan siang',
            'transaction_date' => '2026-06-23',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/transactions/{$transaction->id}/duplicate");

        $response->assertCreated()
            ->assertJsonFragment([
                'type' => 'expense',
                'amount' => '75000.00',
                'note' => 'Makan siang (copy)',
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 75000.00,
            'note' => 'Makan siang (copy)',
        ]);
    }

    public function test_transactions_list_ordered_by_date_desc(): void
    {
        $old = Transaction::factory()->for($this->user)->for($this->category)->create([
            'transaction_date' => '2026-01-01',
        ]);
        $new = Transaction::factory()->for($this->user)->for($this->category)->create([
            'transaction_date' => '2026-06-23',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/transactions');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[count($data) - 1]['id']);
    }
}
