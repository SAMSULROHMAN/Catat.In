<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_their_categories(): void
    {
        Category::factory()->count(3)->for($this->user)->create();

        $response = $this->actingAs($this->user)->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_custom_category(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => 'Shopping',
            'icon' => '🛒',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Shopping', 'icon' => '🛒']);

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Shopping',
            'is_default' => false,
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => '',
        ]);

        $response->assertInvalid(['name']);
    }

    public function test_category_name_max_255_characters(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertInvalid(['name']);
    }

    public function test_user_cannot_exceed_20_custom_categories(): void
    {
        Category::factory()->count(20)->for($this->user)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => 'Extra Category',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_create_category_when_under_limit(): void
    {
        Category::factory()->count(19)->for($this->user)->create(['is_default' => false]);

        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => 'Category 20',
        ]);

        $response->assertCreated();
    }

    public function test_user_can_update_custom_category(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'name' => 'Old Name',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_cannot_update_default_category_name(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'name' => 'Default Category',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/categories/{$category->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_custom_category(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_default_category(): void
    {
        $category = Category::factory()->for($this->user)->create([
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/categories/{$category->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_access_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($otherUser)->create();

        $this->actingAs($this->user)->deleteJson("/api/categories/{$category->id}")
            ->assertNotFound();

        $this->actingAs($this->user)->putJson("/api/categories/{$category->id}", [
            'name' => 'Hacked',
        ])->assertNotFound();
    }

    public function test_guest_cannot_access_categories(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->postJson('/api/categories', ['name' => 'Test'])->assertUnauthorized();
    }
}
