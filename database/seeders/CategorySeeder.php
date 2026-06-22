<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user) {
            Category::seedDefaultsFor($user);
        });
    }
}
