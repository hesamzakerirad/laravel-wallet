<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\TestCase;

class IdempotencyTest extends TestCase
{
    public function test_a_repeated_deposit_key_moves_money_once(): void
    {
        $user = $this->createUser();

        $first = $user->depositOrFail(100, key: 'job-1');
        $second = $user->depositOrFail(100, key: 'job-1');
        $this->assertTrue($user->deposit(100, key: 'job-1'));

        $this->assertTrue($first->is($second));
        $this->assertSame(100.0, $user->fresh()->balance());
        $this->assertDatabaseCount('wallet_logs', 1);
    }

    public function test_a_repeated_withdraw_key_moves_money_once(): void
    {
        $user = $this->createUser();
        $user->deposit(100);

        $first = $user->withdrawOrFail(60, key: 'refund-7');
        // The balance (40) no longer covers 60, but the retry must still return the first log.
        $second = $user->withdrawOrFail(60, key: 'refund-7');
        $this->assertTrue($user->withdraw(60, key: 'refund-7'));

        $this->assertTrue($first->is($second));
        $this->assertSame(40.0, $user->fresh()->balance());
        $this->assertDatabaseCount('wallet_logs', 2);
    }

    public function test_different_keys_and_no_key_move_money_each_time(): void
    {
        $user = $this->createUser();

        $user->deposit(10, key: 'a');
        $user->deposit(10, key: 'b');
        $user->deposit(10);
        $user->deposit(10);

        $this->assertSame(40.0, $user->fresh()->balance());
        $this->assertDatabaseCount('wallet_logs', 4);
    }
}
