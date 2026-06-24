<?php

namespace Tests\Feature\Export;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create(['name' => 'Makanan']);
    }

    public function test_guest_cannot_access_export_page(): void
    {
        $this->get('/exports')->assertRedirect('/login');
    }

    public function test_export_page_can_be_rendered(): void
    {
        $this->actingAs($this->user)
            ->get('/exports')
            ->assertOk()
            ->assertSee('Ekspor Transaksi')
            ->assertSee('Excel')
            ->assertSee('CSV');
    }

    public function test_can_export_all_transactions_as_csv(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->count(3)->create();

        $response = $this->actingAs($this->user)->get('/api/exports/csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="transaksi.csv"');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(4, $lines);
        $this->assertStringContainsString('Tanggal,Deskripsi,Kategori,Jenis,Nominal', $lines[0]);
    }

    public function test_can_export_filtered_transactions_as_csv(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->create([
            'type' => 'income',
            'transaction_date' => '2026-06-01',
        ]);
        Transaction::factory()->for($this->user)->for($this->category)->create([
            'type' => 'expense',
            'transaction_date' => '2026-06-15',
        ]);

        $response = $this->actingAs($this->user)->get('/api/exports/csv?type=income');

        $response->assertOk();
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(2, $lines);
    }

    public function test_can_export_transactions_as_excel(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->count(2)->create();

        $response = $this->actingAs($this->user)->get('/api/exports/excel');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="transaksi.xlsx"');
    }

    public function test_export_respects_date_range_filter(): void
    {
        Transaction::factory()->for($this->user)->for($this->category)->create([
            'transaction_date' => '2026-05-01',
        ]);
        Transaction::factory()->for($this->user)->for($this->category)->create([
            'transaction_date' => '2026-06-15',
        ]);
        Transaction::factory()->for($this->user)->for($this->category)->create([
            'transaction_date' => '2026-07-01',
        ]);

        $response = $this->actingAs($this->user)->get('/api/exports/csv?start_date=2026-06-01&end_date=2026-06-30');

        $response->assertOk();
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(2, $lines);
    }

    public function test_guest_cannot_export(): void
    {
        $this->get('/api/exports/csv')->assertUnauthorized();
        $this->get('/api/exports/excel')->assertUnauthorized();
    }

    public function test_export_returns_empty_file_when_no_transactions(): void
    {
        $response = $this->actingAs($this->user)->get('/api/exports/csv');

        $response->assertOk();
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(1, $lines);
    }
}
