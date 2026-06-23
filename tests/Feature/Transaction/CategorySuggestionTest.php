<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Category::seedDefaultsFor($this->user);
    }

    public function test_suggest_category_returns_makanan_for_food_note(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=makan%20nasi%20goreng');

        $response->assertOk();
        $response->assertJsonStructure(['suggested_category' => ['id', 'name', 'icon']]);

        $suggested = $response->json('suggested_category');
        $this->assertEquals('Makanan', $suggested['name']);
    }

    public function test_suggest_category_returns_transport_for_transport_note(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=bensin%20motor');

        $response->assertOk();
        $suggested = $response->json('suggested_category');
        $this->assertEquals('Transport', $suggested['name']);
    }

    public function test_suggest_category_returns_null_category_for_empty_note(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=');

        $response->assertOk();
        $suggested = $response->json('suggested_category');
        $this->assertNotNull($suggested);
        $this->assertEquals('Lainnya', $suggested['name']);
    }

    public function test_suggest_category_returns_lainnya_for_unknown_note(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=xyzabc');

        $response->assertOk();
        $suggested = $response->json('suggested_category');
        $this->assertNotNull($suggested);
        $this->assertEquals('Lainnya', $suggested['name']);
    }

    public function test_suggest_category_returns_lainnya_for_random_text(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=blablabla%20qwerty');

        $response->assertOk();
        $suggested = $response->json('suggested_category');
        $this->assertNotNull($suggested);
        $this->assertEquals('Lainnya', $suggested['name']);
    }

    public function test_suggest_category_requires_authentication(): void
    {
        $response = $this->getJson('/api/transactions/suggest-category?note=makan');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_suggest(): void
    {
        $response = $this->getJson('/api/transactions/suggest-category?note=makan');
        $response->assertUnauthorized();
    }

    public function test_suggest_category_works_with_note_containing_special_chars(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/transactions/suggest-category?note=makan%20siang%20%40%23%24');

        $response->assertOk();
        $suggested = $response->json('suggested_category');
        $this->assertEquals('Makanan', $suggested['name']);
    }
}
