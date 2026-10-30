<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_a_sales_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = Sale::create([
            'invoice_number' => 'TRX-PDF-01',
            'user_id' => $admin->id,
            'total' => 12000,
            'amount_paid' => 12000,
            'change_due' => 0,
        ]);
        $sale->items()->create([
            'product_name' => 'Teh Botol',
            'unit_price' => 6000,
            'unit_cost' => 4000,
            'quantity' => 2,
            'subtotal' => 12000,
        ]);

        $date = now();
        $response = $this->actingAs($admin)->get(route('laporan.pdf', [
            'from' => $date->toDateString(),
            'to' => $date->toDateString(),
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'attachment; filename=laporan-penjualan-'.$date->format('Ymd').'-'.$date->format('Ymd').'.pdf'
            );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_rejects_an_end_date_before_the_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('laporan.pdf', ['from' => '2026-10-10', 'to' => '2026-10-09']))
            ->assertRedirect()
            ->assertSessionHasErrors('to');
    }

    public function test_pdf_requires_an_admin(): void
    {
        $this->get(route('laporan.pdf'))->assertRedirect(route('login'));
    }
}
