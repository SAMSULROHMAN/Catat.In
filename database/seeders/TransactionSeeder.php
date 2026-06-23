<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user) {
            $categories = $user->categories;

            if ($categories->isEmpty()) {
                return;
            }

            $transactions = [
                ['type' => 'expense', 'amount' => 25000, 'note' => 'Nasi goreng + es teh', 'days_ago' => 0],
                ['type' => 'expense', 'amount' => 15000, 'note' => 'Bensin motor', 'days_ago' => 1],
                ['type' => 'income', 'amount' => 5000000, 'note' => 'Gaji bulanan', 'days_ago' => 2],
                ['type' => 'expense', 'amount' => 150000, 'note' => 'Belanja bulanan', 'days_ago' => 3],
                ['type' => 'expense', 'amount' => 50000, 'note' => 'Pulsa & kuota', 'days_ago' => 4],
                ['type' => 'income', 'amount' => 1000000, 'note' => 'Freelance web', 'days_ago' => 5],
                ['type' => 'expense', 'amount' => 75000, 'note' => 'Gojek', 'days_ago' => 6],
                ['type' => 'expense', 'amount' => 200000, 'note' => 'Makan malam', 'days_ago' => 7],
                ['type' => 'expense', 'amount' => 35000, 'note' => 'Cemilan', 'days_ago' => 10],
                ['type' => 'income', 'amount' => 500000, 'note' => 'Bonus project', 'days_ago' => 14],
            ];

            foreach ($transactions as $t) {
                Transaction::factory()->for($user)->for($categories->random())->create([
                    'type' => $t['type'],
                    'amount' => $t['amount'],
                    'note' => $t['note'],
                    'transaction_date' => now()->subDays($t['days_ago'])->format('Y-m-d'),
                ]);
            }
        });
    }
}
