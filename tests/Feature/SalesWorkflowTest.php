<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_cashier_are_redirected_to_their_own_dashboards_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin'));

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('kasir'));
    }

    public function test_cashier_can_complete_a_sale_and_stock_is_decremented(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'name' => 'Kopi',
            'description' => null,
            'price' => 5000,
            'stock' => 8,
        ]);

        $response = $this->actingAs($cashier)->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-SUCCESS-01',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
            'amount_paid' => 20000,
            'payment_method' => 'cash',
        ]);

        $sale = Sale::with('items')->firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_name' => 'Kopi',
            'unit_price' => 5000,
            'quantity' => 3,
            'subtotal' => 15000,
        ]);
        $this->assertSame(15000, $sale->total);
        $this->assertSame(5000, $sale->change_due);
        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee($sale->invoice_number);

        $otherCashier = User::factory()->create(['role' => 'kasir']);
        $this->actingAs($otherCashier)
            ->get(route('sales.show', $sale))
            ->assertForbidden();
    }

    public function test_cashier_page_loads_the_product_search_and_checkout_script(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        Product::create([
            'name' => 'Kopi',
            'price' => 5000,
            'stock' => 8,
        ]);

        $this->actingAs($cashier)
            ->get(route('kasir'))
            ->assertOk()
            ->assertSee('product-search')
            ->assertSee('Kopi')
            ->assertSee('function renderProducts()')
            ->assertSee('Bayar & cetak', false);
    }

    public function test_sale_is_rejected_when_stock_or_payment_is_insufficient(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'name' => 'Susu',
            'description' => null,
            'price' => 10000,
            'stock' => 2,
        ]);

        $this->actingAs($cashier)->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-OUT-OF-STOCK-01',
            'products' => [['product_id' => $product->id, 'quantity' => 3]],
            'amount_paid' => 50000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('products');

        $this->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-INSUFFICIENT-PAYMENT-01',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 5000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    }

    public function test_checkout_saves_customer_payment_method_discounts_and_fees(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'name' => 'Kopi',
            'price' => 5000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-DISCOUNT-01',
            'products' => [['product_id' => $product->id, 'quantity' => 3]],
            'customer_name' => 'Pelanggan A',
            'customer_reference' => '0812345678',
            'discount_percent' => 10,
            'discount_amount' => 500,
            'tax_amount' => 500,
            'additional_fee' => 1000,
            'amount_paid' => 16000,
            'payment_method' => 'qris',
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'customer_name' => 'Pelanggan A',
            'customer_reference' => '0812345678',
            'subtotal' => 15000,
            'discount_percent' => 10,
            'discount_amount' => 2000,
            'tax_amount' => 500,
            'additional_fee' => 1000,
            'total' => 14500,
            'amount_paid' => 16000,
            'change_due' => 1500,
            'payment_method' => 'qris',
        ]);
    }

    public function test_checkout_rejects_discounts_that_exceed_the_subtotal(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'name' => 'Roti',
            'price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-INVALID-DISCOUNT-01',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
            'discount_percent' => 50,
            'discount_amount' => 3000,
            'amount_paid' => 5000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('discount_amount');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_users_cannot_open_the_other_role_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($admin)->get(route('kasir'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin'))->assertForbidden();
    }
}
