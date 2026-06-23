<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'limit_amount' => fake()->randomFloat(2, 100000, 5000000),
            'period_month' => now()->format('Y-m'),
        ];
    }

    public function forMonth(string $periodMonth): static
    {
        return $this->state(fn (array $attributes) => [
            'period_month' => $periodMonth,
        ]);
    }

    public function currentMonth(): static
    {
        return $this->forMonth(now()->format('Y-m'));
    }

    public function previousMonth(): static
    {
        return $this->forMonth(now()->subMonth()->format('Y-m'));
    }
}