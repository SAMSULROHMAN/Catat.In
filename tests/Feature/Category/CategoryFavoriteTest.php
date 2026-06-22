<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_toggle_favorite_on_category(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'is_favorite' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/categories/{$category->id}/favorite");

        $response->assertOk()
            ->assertJsonFragment(['is_favorite' => true]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_favorite' => true,
        ]);
    }

    public function test_user_can_unset_favorite(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'is_favorite' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/categories/{$category->id}/favorite");

        $response->assertOk()
            ->assertJsonFragment(['is_favorite' => false]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_favorite' => false,
        ]);
    }

    public function test_user_can_toggle_favorite_multiple_times(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'is_favorite' => false,
        ]);

        $this->actingAs($this->user)->postJson("/api/categories/{$category->id}/favorite")
            ->assertOk();

        $category->refresh();
        $this->assertTrue($category->is_favorite);

        $this->actingAs($this->user)->postJson("/api/categories/{$category->id}/favorite")
            ->assertOk();

        $category->refresh();
        $this->assertFalse($category->is_favorite);
    }

    public function test_user_cannot_toggle_favorite_on_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($otherUser)->create();

        $this->actingAs($this->user)->postJson("/api/categories/{$category->id}/favorite")
            ->assertNotFound();
    }

    public function test_categories_are_ordered_with_favorites_first(): void
    {
        Category::factory()->for($this->user)->create([
            'name' => 'Regular',
            'is_favorite' => false,
        ]);
        Category::factory()->for($this->user)->create([
            'name' => 'Favorite',
            'is_favorite' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/categories');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Favorite', $data[0]['name']);
        $this->assertEquals('Regular', $data[1]['name']);
    }
}
