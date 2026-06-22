<?php

namespace Tests\Unit\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    public function test_category_has_correct_fillable_attributes(): void
    {
        $category = new Category;

        $this->assertEquals(['name', 'icon', 'is_default', 'is_favorite'], $category->getFillable());
    }

    public function test_category_default_values(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'name' => 'Test',
        ]);

        $this->assertFalse($category->is_default);
        $this->assertFalse($category->is_favorite);
    }

    public function test_user_has_many_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->for($user)->create();

        $this->assertCount(3, $user->categories);
    }

    public function test_scope_favorites_orders_by_created_at_desc(): void
    {
        $user = User::factory()->create();

        $cat1 = Category::factory()->for($user)->create([
            'name' => 'Favorite 1',
            'is_favorite' => true,
            'created_at' => now()->subDay(),
        ]);
        $cat2 = Category::factory()->for($user)->create([
            'name' => 'Favorite 2',
            'is_favorite' => true,
            'created_at' => now(),
        ]);
        $cat3 = Category::factory()->for($user)->create([
            'name' => 'Not Favorite',
            'is_favorite' => false,
        ]);

        $favorites = Category::favorites()->get();

        $this->assertCount(2, $favorites);
        $this->assertEquals($cat2->id, $favorites->first()->id);
        $this->assertEquals($cat1->id, $favorites->last()->id);
    }

    public function test_scope_defaults_only_returns_default_categories(): void
    {
        $user = User::factory()->create();

        Category::factory()->for($user)->create(['name' => 'Default', 'is_default' => true]);
        Category::factory()->for($user)->create(['name' => 'Custom', 'is_default' => false]);

        $defaults = Category::defaults()->get();

        $this->assertCount(1, $defaults);
        $this->assertEquals('Default', $defaults->first()->name);
    }

    public function test_scope_for_user_returns_only_user_categories(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Category::factory()->count(2)->for($user1)->create();
        Category::factory()->count(3)->for($user2)->create();

        $this->assertCount(2, Category::forUser($user1->id)->get());
        $this->assertCount(3, Category::forUser($user2->id)->get());
    }

    public function test_scope_custom_returns_only_non_default_categories(): void
    {
        $user = User::factory()->create();

        Category::factory()->for($user)->create(['name' => 'Default', 'is_default' => true]);
        Category::factory()->for($user)->create(['name' => 'Custom 1', 'is_default' => false]);
        Category::factory()->for($user)->create(['name' => 'Custom 2', 'is_default' => false]);

        $customs = Category::custom()->get();

        $this->assertCount(2, $customs);
    }

    public function test_scope_ordered_sorts_favorites_first_then_by_name(): void
    {
        $user = User::factory()->create();

        Category::factory()->for($user)->create([
            'name' => 'Z Category',
            'is_favorite' => false,
        ]);
        Category::factory()->for($user)->create([
            'name' => 'A Favorite',
            'is_favorite' => true,
        ]);
        Category::factory()->for($user)->create([
            'name' => 'B Category',
            'is_favorite' => false,
        ]);

        $ordered = Category::ordered()->get();

        $this->assertEquals('A Favorite', $ordered[0]->name);
        $this->assertEquals('B Category', $ordered[1]->name);
        $this->assertEquals('Z Category', $ordered[2]->name);
    }
}
