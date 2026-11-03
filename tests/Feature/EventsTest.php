<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Events\WalletDeposited;
use HesamRad\LaravelWallet\Events\WalletWithdrawn;
use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class EventsTest extends TestCase
{
    public function test_events_carry_the_log(): void
    {
        Event::fake([WalletDeposited::class, WalletWithdrawn::class]);
        $user = $this->createUser();

        $deposit = $user->depositOrFail(10);
        $withdraw = $user->withdrawOrFail(4);

        Event::assertDispatchedTimes(WalletDeposited::class, 1);
        Event::assertDispatchedTimes(WalletWithdrawn::class, 1);
        Event::assertDispatched(WalletDeposited::class, fn ($event) => $event->log->is($deposit));
        Event::assertDispatched(WalletWithdrawn::class, fn ($event) => $event->log->is($withdraw));
    }

    public function test_events_wait_for_the_outer_transaction_to_commit(): void
    {
        Event::fake([WalletDeposited::class]);
        $user = $this->createUser();

        DB::transaction(function () use ($user) {
            $user->deposit(10);

            Event::assertNotDispatched(WalletDeposited::class);
        });

        Event::assertDispatchedTimes(WalletDeposited::class, 1);
    }

    public function test_no_event_is_dispatched_when_the_outer_transaction_rolls_back(): void
    {
        Event::fake([WalletDeposited::class]);
        $user = $this->createUser();

        try {
            DB::transaction(function () use ($user) {
                $user->deposit(10);

                throw new \RuntimeException('Rollback.');
            });
        } catch (\RuntimeException) {
            //
        }

        Event::assertNotDispatched(WalletDeposited::class);
        $this->assertDatabaseCount('wallet_logs', 0);
    }

    public function test_no_event_for_failed_or_repeated_transactions(): void
    {
        Event::fake([WalletDeposited::class, WalletWithdrawn::class]);
        $user = $this->createUser();

        $user->deposit(5, key: 'once');
        $user->deposit(5, key: 'once');

        try {
            $user->withdraw(100);
        } catch (InsufficientBalanceException) {
            //
        }

        Event::assertDispatchedTimes(WalletDeposited::class, 1);
        Event::assertNotDispatched(WalletWithdrawn::class);
    }
}
