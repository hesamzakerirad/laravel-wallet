<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Models\Wallet;
use HesamRad\LaravelWallet\Tests\TestCase;

class FreshBalanceTest extends TestCase
{
    public function test_fresh_balance_reads_the_database_instead_of_the_cached_relation(): void
    {
        $user = $this->createUser();
        $user->deposit(10);

        // Another process changes the balance behind the cached relation.
        Wallet::query()->whereKey($user->wallet->id)->update(['balance' => 99]);

        $this->assertSame(10.0, $user->balance());
        $this->assertSame(99.0, $user->freshBalance());
        $this->assertSame(10.0, $user->balance(), 'freshBalance() must not replace the cached relation.');
    }

    public function test_fresh_balance_is_zero_without_a_wallet(): void
    {
        $this->assertSame(0.0, $this->createUser()->freshBalance());
    }

    public function test_fresh_balance_is_an_int_in_integer_mode(): void
    {
        config(['wallet.amounts' => 'integer']);
        $user = $this->createUser();

        $this->assertSame(0, $user->freshBalance());

        $user->deposit(500);

        $this->assertSame(500, $user->freshBalance());
    }
}
