<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TransactionSuggestionLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Category::seedDefaultsFor($this->user);
    }

    public function test_logs_when_suggestion_is_accepted(): void
    {
        Log::spy();

        $makanan = Category::forUser($this->user->id)->where('name', 'Makanan')->first();

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $makanan->id,
            'type' => 'expense',
            'amount' => 25000,
            'note' => 'makan nasi goreng',
            'transaction_date' => '2026-06-23',
        ])->assertCreated();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return $message === 'CategorySuggestion'
                    && $context['accepted'] === true
                    && $context['note'] === 'makan nasi goreng';
            })->once();
    }

    public function test_logs_when_suggestion_is_overridden(): void
    {
        Log::spy();

        $transport = Category::forUser($this->user->id)->where('name', 'Transport')->first();

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $transport->id,
            'type' => 'expense',
            'amount' => 50000,
            'note' => 'makan nasi goreng',
            'transaction_date' => '2026-06-23',
        ])->assertCreated();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return $message === 'CategorySuggestion'
                    && $context['accepted'] === false
                    && $context['note'] === 'makan nasi goreng';
            })->once();
    }

    public function test_does_not_log_when_note_is_empty(): void
    {
        Log::spy();

        $lainnya = Category::forUser($this->user->id)->where('name', 'Lainnya')->first();

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $lainnya->id,
            'type' => 'expense',
            'amount' => 10000,
            'note' => '',
            'transaction_date' => '2026-06-23',
        ])->assertCreated();

        Log::shouldNotHaveReceived('info');
    }

    public function test_logs_correct_user_id(): void
    {
        Log::spy();

        $makanan = Category::forUser($this->user->id)->where('name', 'Makanan')->first();

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'category_id' => $makanan->id,
            'type' => 'expense',
            'amount' => 15000,
            'note' => 'kopi',
            'transaction_date' => '2026-06-23',
        ])->assertCreated();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return $message === 'CategorySuggestion'
                    && $context['user_id'] === $this->user->id;
            })->once();
    }
}
