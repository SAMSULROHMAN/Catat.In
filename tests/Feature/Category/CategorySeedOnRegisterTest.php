<?php

namespace Tests\Feature\Category;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySeedOnRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_9_default_categories_on_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertValid();
        $this->assertAuthenticated();

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);

        $categories = $user->categories;
        $this->assertCount(9, $categories);

        $expectedNames = ['Makanan', 'Transport', 'Belanja', 'Hiburan', 'Tagihan', 'Gaji', 'Freelance', 'Investasi', 'Lainnya'];
        $actualNames = $categories->pluck('name')->toArray();
        sort($actualNames);
        sort($expectedNames);
        $this->assertEquals($expectedNames, $actualNames);
    }

    public function test_default_categories_have_icons(): void
    {
        $user = User::factory()->create();
        $this->seedDefaultCategories($user);

        $categories = $user->categories;
        foreach ($categories as $category) {
            $this->assertNotEmpty($category->icon, "Category '{$category->name}' should have an icon");
        }
    }

    public function test_default_categories_are_marked_as_default(): void
    {
        $user = User::factory()->create();
        $this->seedDefaultCategories($user);

        $categories = $user->categories;
        foreach ($categories as $category) {
            $this->assertTrue($category->is_default, "Category '{$category->name}' should be marked as default");
        }
    }

    public function test_default_categories_are_not_marked_as_favorite(): void
    {
        $user = User::factory()->create();
        $this->seedDefaultCategories($user);

        $favorites = $user->categories()->where('is_favorite', true)->count();
        $this->assertEquals(0, $favorites);
    }

    public function test_user_can_still_create_custom_categories_after_seeding(): void
    {
        $user = User::factory()->create();
        $this->seedDefaultCategories($user);

        $response = $this->actingAs($user)->postJson('/api/categories', [
            'name' => 'My Custom Category',
            'icon' => '💰',
        ]);

        $response->assertCreated();
        $this->assertCount(10, $user->fresh()->categories);
    }

    private function seedDefaultCategories(User $user): void
    {
        $defaults = [
            ['name' => 'Makanan', 'icon' => '🍔'],
            ['name' => 'Transport', 'icon' => '🚗'],
            ['name' => 'Belanja', 'icon' => '🛒'],
            ['name' => 'Hiburan', 'icon' => '🎮'],
            ['name' => 'Tagihan', 'icon' => '📄'],
            ['name' => 'Gaji', 'icon' => '💰'],
            ['name' => 'Freelance', 'icon' => '💻'],
            ['name' => 'Investasi', 'icon' => '📈'],
            ['name' => 'Lainnya', 'icon' => '📌'],
        ];

        foreach ($defaults as $default) {
            $user->categories()->create(array_merge($default, [
                'is_default' => true,
            ]));
        }
    }
}
