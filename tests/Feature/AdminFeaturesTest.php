<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_categories_suppliers_and_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.categories.store'), [
            'name' => 'Minuman',
            'description' => 'Minuman dingin dan panas',
        ])->assertRedirect();
        $category = Category::where('name', 'Minuman')->firstOrFail();

        $this->post(route('admin.suppliers.store'), [
            'name' => 'Pemasok Utama',
            'contact_name' => 'Sari',
            'phone' => '0812345678',
        ])->assertRedirect();
        $supplier = Supplier::where('name', 'Pemasok Utama')->firstOrFail();

        $this->post(route('products.store'), [
            'name' => 'Teh Botol',
            'category_id' => $category->id,
            'barcode' => '899123',
            'price' => 6000,
            'cost_price' => 3500,
            'stock' => 4,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Teh Botol',
            'category_id' => $category->id,
            'barcode' => '899123',
            'cost_price' => 3500,
        ]);
        $this->assertSame('Pemasok Utama', $supplier->name);
    }

    public function test_admin_stock_receipt_updates_inventory_and_tracks_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Beras',
            'price' => 18000,
            'cost_price' => 14000,
            'stock' => 5,
        ]);
        $supplier = Supplier::create(['name' => 'Sumber Pangan']);

        $this->actingAs($admin)->post(route('admin.stock.store'), [
            'supplier_id' => $supplier->id,
            'notes' => 'Faktur 21',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 7,
                'unit_cost' => 14500,
            ]],
        ])->assertRedirect(route('admin.stock'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 12,
            'cost_price' => 14500,
        ]);
        $this->assertDatabaseHas('stock_receipts', [
            'supplier_id' => $supplier->id,
            'received_by' => $admin->id,
            'total_cost' => 101500,
            'notes' => 'Faktur 21',
        ]);
        $this->assertDatabaseHas('stock_receipt_items', [
            'product_id' => $product->id,
            'quantity' => 7,
            'unit_cost' => 14500,
        ]);
    }

    public function test_admin_can_manage_users_but_cannot_remove_the_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir-baru@example.com',
            'role' => 'kasir',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $cashier = User::where('email', 'kasir-baru@example.com')->firstOrFail();
        $this->assertSame('kasir', $cashier->role);

        $this->put(route('admin.users.update', $cashier), [
            'name' => 'Kasir Diperbarui',
            'email' => $cashier->email,
            'role' => 'kasir',
        ])->assertRedirect();

        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'kasir',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'name' => 'Kasir Diperbarui',
            'password' => $cashier->password,
        ]);

        $this->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $saleCashier = User::factory()->create(['role' => 'kasir']);
        $sale = Sale::create([
            'invoice_number' => 'TRX-KEEP-USER',
            'user_id' => $saleCashier->id,
            'total' => 1000,
            'amount_paid' => 1000,
            'change_due' => 0,
        ]);
        $this->delete(route('admin.users.destroy', $saleCashier))
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'user_id' => $saleCashier->id]);
    }

    public function test_reports_use_cost_snapshots_and_export_csv_for_a_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $sale = Sale::create([
            'invoice_number' => 'TRX-REPORT-01',
            'user_id' => $cashier->id,
            'total' => 12000,
            'amount_paid' => 15000,
            'change_due' => 3000,
        ]);
        $sale->items()->create([
            'product_name' => '=Formula Product',
            'unit_price' => 6000,
            'unit_cost' => 4000,
            'quantity' => 2,
            'subtotal' => 12000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Rp 4.000')
            ->assertSee('Rp 12.000');

        $response = $this->get(route('admin.reports.export', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("'=Formula Product", $response->streamedContent());
    }
}
