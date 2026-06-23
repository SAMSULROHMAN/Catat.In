<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\User;
use App\Services\CategorySuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CategorySuggestionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Category::seedDefaultsFor($this->user);
        $this->service = new CategorySuggestionService;
    }

    public function test_suggests_makanan_for_food_keywords(): void
    {
        $keywords = ['makan nasi goreng', 'kopi hitam', 'beli mie ayam', 'soto betawi', 'bakso'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Makanan', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_transport_for_transport_keywords(): void
    {
        $keywords = ['bensin motor', 'isi solar', 'bayar tol', 'parkir mobil', 'gojek ke kantor'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Transport', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_belanja_for_shopping_keywords(): void
    {
        $keywords = ['belanja bulanan', 'indomaret', 'alfamaret', 'pasar tradisional', 'baju baru'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Belanja', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_hiburan_for_entertainment_keywords(): void
    {
        $keywords = ['nonton bioskop', 'netflix', 'spotify premium', 'game steam', 'liburan'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Hiburan', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_tagihan_for_bills_keywords(): void
    {
        $keywords = ['bayar listrik', 'tagihan air pdam', 'pulsa', 'kuota internet', 'bpjs'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Tagihan', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_gaji_for_income_keywords(): void
    {
        $keywords = ['gaji bulanan', 'honor proyek', 'bonus', 'pendapatan'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Gaji', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_freelance_for_freelance_keywords(): void
    {
        $keywords = ['freelance', 'proyek desain', 'coding project', 'konten'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Freelance', $category->name, "Failed for: $note");
        }
    }

    public function test_suggests_investasi_for_investment_keywords(): void
    {
        $keywords = ['investasi saham', 'reksadana', 'beli emas', 'deposito'];

        foreach ($keywords as $note) {
            $categoryId = $this->service->suggest($this->user->id, $note);
            $category = Category::find($categoryId);

            $this->assertNotNull($category, "No suggestion for: $note");
            $this->assertEquals('Investasi', $category->name, "Failed for: $note");
        }
    }

    public function test_returns_lainnya_for_unknown_keywords(): void
    {
        $lainnya = Category::forUser($this->user->id)->where('name', 'Lainnya')->first();

        $categoryId = $this->service->suggest($this->user->id, 'xyzabc random text');
        $this->assertEquals($lainnya->id, $categoryId);
    }

    public function test_returns_lainnya_for_empty_note(): void
    {
        $lainnya = Category::forUser($this->user->id)->where('name', 'Lainnya')->first();

        $categoryId = $this->service->suggest($this->user->id, '');
        $this->assertEquals($lainnya->id, $categoryId);

        $categoryId = $this->service->suggest($this->user->id, '   ');
        $this->assertEquals($lainnya->id, $categoryId);
    }

    public function test_suggest_with_category_returns_category_model(): void
    {
        $category = $this->service->suggestWithCategory($this->user->id, 'makan nasi');
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Makanan', $category->name);
    }

    public function test_suggest_with_category_returns_null_for_empty_note(): void
    {
        $category = $this->service->suggestWithCategory($this->user->id, '');
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Lainnya', $category->name);
    }

    public function test_suggest_is_case_insensitive(): void
    {
        $categoryId = $this->service->suggest($this->user->id, 'MAKAN SIANG');
        $category = Category::find($categoryId);
        $this->assertEquals('Makanan', $category->name);

        $categoryId = $this->service->suggest($this->user->id, 'GoJeK');
        $category = Category::find($categoryId);
        $this->assertEquals('Transport', $category->name);
    }

    public function test_suggest_matches_partial_words(): void
    {
        $categoryId = $this->service->suggest($this->user->id, 'makanan ringan');
        $category = Category::find($categoryId);
        $this->assertEquals('Makanan', $category->name);
    }

    public function test_get_keyword_map_returns_array(): void
    {
        $map = CategorySuggestionService::getKeywordMap();
        $this->assertIsArray($map);
        $this->assertArrayHasKey('Makanan', $map);
        $this->assertArrayHasKey('Transport', $map);
        $this->assertArrayHasKey('Belanja', $map);
        $this->assertArrayHasKey('Hiburan', $map);
        $this->assertArrayHasKey('Tagihan', $map);
        $this->assertArrayHasKey('Gaji', $map);
        $this->assertArrayHasKey('Freelance', $map);
        $this->assertArrayHasKey('Investasi', $map);
        $this->assertCount(8, $map);
    }
}
