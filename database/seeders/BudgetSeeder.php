<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    private array $budgetLimits = [
        'Makanan' => 1500000,
        'Transport' => 500000,
        'Belanja' => 1000000,
        'Hiburan' => 300000,
        'Tagihan' => 800000,
        'Lainnya' => 200000,
    ];

    public function run(): void
    {
        User::query()->each(function (User $user) {
            $categories = $user->categories;

            foreach ($this->budgetLimits as $categoryName => $limit) {
                $category = $categories->firstWhere('name', $categoryName);

                if (! $category) {
                    continue;
                }

                Budget::factory()->for($user)->for($category)->create([
                    'limit_amount' => $limit,
                    'period_month' => now()->format('Y-m'),
                ]);
            }
        });
    }
}