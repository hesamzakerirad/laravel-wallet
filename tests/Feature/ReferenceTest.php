<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\Fixtures\Order;
use HesamRad\LaravelWallet\Tests\TestCase;

class ReferenceTest extends TestCase
{
    public function test_a_reference_model_is_stored_on_the_log(): void
    {
        $user = $this->createUser();
        $order = Order::create();

        $user->deposit(50, reference: $order);
        $log = $user->withdrawOrFail(20, 'Order payment', reference: $order);

        $this->assertSame(Order::class, $log->reference_type);
        $this->assertTrue($log->fresh()->reference->is($order));
        $this->assertDatabaseCount('wallet_logs', 2);
        $this->assertDatabaseHas('wallet_logs', [
            'action' => 'deposit',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    public function test_the_reference_is_optional(): void
    {
        $log = $this->createUser()->depositOrFail(5);

        $this->assertNull($log->reference_type);
        $this->assertNull($log->reference_id);
        $this->assertNull($log->fresh()->reference);
    }
}
