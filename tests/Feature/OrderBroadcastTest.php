<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_cashier_checkout_dispatches_order_placed(): void
    {
        Event::fake([OrderPlaced::class]);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'name' => 'Kopi',
            'description' => null,
            'price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('kasir.sales.store'), [
            'invoice_number' => 'TRX-BROADCAST-01',
            'products' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 10000,
            'payment_method' => 'cash',
        ])->assertRedirect();

        Event::assertDispatched(
            OrderPlaced::class,
            fn (OrderPlaced $event): bool => $event->order->invoice_number === 'TRX-BROADCAST-01'
                && $event->user->is($cashier)
                && $event->broadcastWith()['total'] === 10000,
        );
    }

    public function test_private_notification_channel_is_limited_to_its_user(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $cashier = User::factory()->create(['role' => 'kasir']);
        $otherCashier = User::factory()->create(['role' => 'kasir']);
        $channelPayload = [
            'socket_id' => '123.456',
            'channel_name' => 'private-user.'.$cashier->id,
        ];

        $this->actingAs($cashier)
            ->postJson('/broadcasting/auth', $channelPayload)
            ->assertOk();

        $channelPayload['channel_name'] = 'private-user.'.$otherCashier->id;

        $this->actingAs($cashier)
            ->postJson('/broadcasting/auth', $channelPayload)
            ->assertForbidden();
    }
}
