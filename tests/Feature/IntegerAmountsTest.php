<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Exceptions\InvalidInputException;
use HesamRad\LaravelWallet\Models\Log;
use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class IntegerAmountsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('wallet.amounts', 'integer');
    }

    public function test_the_migrations_create_integer_columns(): void
    {
        $this->assertSame('integer', Schema::getColumnType('wallets', 'balance'));
        $this->assertSame('integer', Schema::getColumnType('wallet_logs', 'amount'));
        $this->assertSame('integer', Schema::getColumnType('wallet_logs', 'balance_after'));
    }

    public function test_whole_amounts_are_stored_and_cast_to_int(): void
    {
        $user = $this->createUser();

        $user->deposit(1050);
        $log = $user->withdrawOrFail(50.0);

        $this->assertSame(50, $log->amount);
        $this->assertSame(1000, $log->balance_after);
        $this->assertSame(1000, $user->fresh()->wallet->balance);
        $this->assertSame(1000.0, $user->balance());
        $this->assertSame([1050, 1000], Log::query()->orderBy('id')->pluck('balance_after')->all());
    }

    public function test_fractional_amounts_are_refused(): void
    {
        $user = $this->createUser();

        try {
            $user->deposit(10.5);
            $this->fail('InvalidInputException was not thrown.');
        } catch (InvalidInputException) {
            //
        }

        $this->expectException(InvalidInputException::class);
        $user->withdrawOrFail(0.01);
    }
}
