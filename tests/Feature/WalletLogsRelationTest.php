<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Models\Wallet;
use HesamRad\LaravelWallet\Tests\Fixtures\Order;
use HesamRad\LaravelWallet\Tests\Fixtures\User;
use HesamRad\LaravelWallet\Tests\TestCase;

class WalletLogsRelationTest extends TestCase
{
    public function test_wallet_logs_lists_the_owner_logs_newest_first(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $user->deposit(10, 'first');
        $other->deposit(99, 'not mine');
        $user->withdraw(3, 'second');

        $this->assertSame(['second', 'first'], $user->walletLogs()->pluck('description')->all());
        $this->assertSame(['not mine'], $other->walletLogs()->pluck('description')->all());
    }

    public function test_wallet_logs_ignores_wallets_of_other_owner_types_with_the_same_id(): void
    {
        $user = $this->createUser();
        $user->deposit(10);

        $order = Order::create();
        $this->assertSame($user->id, $order->id);
        Wallet::query()->create(['owner_type' => Order::class, 'owner_id' => $order->id])
            ->logs()->create(['amount' => 1, 'action' => 'deposit']);

        $this->assertCount(1, $user->walletLogs);
    }

    public function test_wallet_logs_can_be_eager_loaded_and_paginated(): void
    {
        $user = $this->createUser();
        $user->deposit(1);
        $user->deposit(2);
        $user->deposit(3);

        $users = User::with('walletLogs')->get();
        $page = $user->walletLogs()->paginate(2);

        $this->assertCount(3, $users->first()->walletLogs);
        $this->assertCount(2, $page->items());
        $this->assertSame(3, $page->total());
        $this->assertSame([3.0, 2.0], collect($page->items())->pluck('amount')->all());
    }
}
