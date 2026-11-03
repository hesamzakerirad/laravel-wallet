<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Exceptions\InvalidInputException;
use HesamRad\LaravelWallet\Models\Log;
use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class OrFailTest extends TestCase
{
    public function test_deposit_or_fail_returns_the_created_log(): void
    {
        $user = $this->createUser();

        $log = $user->depositOrFail(25, 'Top up', ['via' => 'card']);

        $this->assertInstanceOf(Log::class, $log);
        $this->assertTrue($log->exists);
        $this->assertSame('deposit', $log->action);
        $this->assertSame(25.0, $log->amount);
        $this->assertSame(25.0, $user->balance());
    }

    public function test_withdraw_or_fail_returns_the_created_log(): void
    {
        $user = $this->createUser();
        $user->deposit(25);

        $log = $user->withdrawOrFail(10, 'Purchase');

        $this->assertSame('withdraw', $log->action);
        $this->assertSame(10.0, $log->amount);
        $this->assertSame(15.0, $user->balance());
    }

    public function test_withdraw_or_fail_throws_on_insufficient_balance(): void
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->createUser()->withdrawOrFail(1);
    }

    public function test_or_fail_methods_throw_on_invalid_input(): void
    {
        $user = $this->createUser();

        $this->expectException(InvalidInputException::class);
        $user->depositOrFail(0);
    }

    public function test_or_fail_methods_throw_database_errors_without_reporting(): void
    {
        $user = $this->createUser();
        $user->deposit(5);

        $handler = \Mockery::mock(ExceptionHandler::class);
        $handler->shouldNotReceive('report');
        $this->app->instance(ExceptionHandler::class, $handler);

        Schema::drop('wallet_logs');

        try {
            $user->depositOrFail(5);
            $this->fail('QueryException was not thrown.');
        } catch (QueryException) {
            //
        }

        try {
            $user->withdrawOrFail(1);
            $this->fail('QueryException was not thrown.');
        } catch (QueryException) {
            //
        }

        $this->assertSame(5.0, $user->fresh()->balance());
    }
}
