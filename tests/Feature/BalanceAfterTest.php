<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Models\Log;
use HesamRad\LaravelWallet\Tests\TestCase;

class BalanceAfterTest extends TestCase
{
    public function test_each_log_stores_the_balance_after_the_transaction(): void
    {
        $user = $this->createUser();

        $user->deposit(100);
        $user->withdraw(30.5);
        $user->deposit(0.25);

        $this->assertSame(
            [100.0, 69.5, 69.75],
            Log::query()->orderBy('id')->pluck('balance_after')->all(),
        );
        $this->assertSame(69.75, $user->fresh()->balance());
    }

    public function test_logs_written_before_the_column_existed_have_a_null_balance_after(): void
    {
        $user = $this->createUser();
        $user->deposit(10);

        Log::query()->update(['balance_after' => null]);

        $this->assertNull(Log::first()->balance_after);
    }
}
