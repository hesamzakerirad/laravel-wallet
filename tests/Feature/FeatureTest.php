<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Exceptions\InvalidInputException;
use HesamRad\LaravelWallet\Tests\TestCase;

/**
 * Pins the pre-existing public API so new features cannot change it.
 */
class FeatureTest extends TestCase
{
    public function test_user_has_zero_balance_when_wallet_is_not_created(): void
    {
        $user = $this->createUser();

        $this->assertSame(0.0, $user->balance());
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_deposit_returns_true_and_increases_balance(): void
    {
        $user = $this->createUser();

        $result = $user->deposit(100.5, 'This is a test.', ['key' => 'value']);

        $this->assertTrue($result);
        $this->assertSame(100.5, $user->balance());
        $this->assertSame(100.5, $user->fresh()->balance());
    }

    public function test_withdraw_returns_true_and_decreases_balance(): void
    {
        $user = $this->createUser();

        $user->deposit(500, 'Initial deposit.');
        $result = $user->withdraw(500, 'Withdrawal test.');

        $this->assertTrue($result);
        $this->assertSame(0.0, $user->balance());
        $this->assertSame(0.0, $user->fresh()->balance());
    }

    public function test_numeric_strings_are_still_accepted(): void
    {
        $user = $this->createUser();

        $this->assertTrue($user->deposit('20.25'));
        $this->assertTrue($user->withdraw('0.25'));
        $this->assertSame(20.0, $user->fresh()->balance());
    }

    public function test_withdraw_throws_on_insufficient_balance_and_keeps_balance(): void
    {
        $user = $this->createUser();
        $user->deposit(10, 'Setup');

        try {
            $user->withdraw(10.10, 'Attempt overdraw.');
            $this->fail('InsufficientBalanceException was not thrown.');
        } catch (InsufficientBalanceException) {
            //
        }

        $this->assertSame(10.0, $user->fresh()->balance());
        $this->assertDatabaseCount('wallet_logs', 1);
    }

    public function test_withdraw_from_missing_wallet_throws_insufficient_balance(): void
    {
        $user = $this->createUser();

        $this->expectException(InsufficientBalanceException::class);
        $user->withdraw(1);
    }

    public function test_deposit_throws_invalid_input_exception_on_zero_amount(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->createUser()->deposit(0, 'Test zero deposit.');
    }

    public function test_deposit_throws_invalid_input_exception_on_negative_amount(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->createUser()->deposit(-10.0, 'Test negative deposit.');
    }

    public function test_withdraw_throws_invalid_input_exception_on_zero_amount(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->createUser()->withdraw(0, 'Test zero withdrawal.');
    }

    public function test_withdraw_throws_invalid_input_exception_on_negative_amount(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->createUser()->withdraw(-10.0, 'Test negative withdrawal.');
    }

    public function test_deposit_action_is_logged_in_database(): void
    {
        $user = $this->createUser();

        $user->deposit(50, 'Test log deposit', ['source' => 'test']);

        $this->assertDatabaseHas('wallet_logs', [
            'wallet_id' => $user->wallet->id,
            'action' => 'deposit',
            'amount' => 50,
            'description' => 'Test log deposit',
        ]);
        $this->assertSame(['source' => 'test'], $user->wallet->logs->first()->meta);
    }

    public function test_withdraw_action_is_logged_in_database(): void
    {
        $user = $this->createUser();

        $user->deposit(20, 'Setup');
        $user->withdraw(10, 'Test log withdrawal');

        $this->assertDatabaseHas('wallet_logs', [
            'wallet_id' => $user->wallet->id,
            'action' => 'withdraw',
            'amount' => 10,
            'description' => 'Test log withdrawal',
        ]);
    }

    public function test_deposit_returns_false_and_does_not_throw_on_database_error(): void
    {
        $user = $this->createUser();
        $user->deposit(5);

        \Illuminate\Support\Facades\Schema::drop('wallet_logs');

        $this->assertFalse($user->deposit(5));
        $this->assertSame(5.0, $user->fresh()->balance());
    }

    public function test_balance_to_string_prefixes_the_currency_symbol(): void
    {
        $user = $this->createUser();
        $user->deposit(12.5);

        $this->assertSame('$12.5', $user->balanceToString());
        $this->assertSame('USD', $user->currency());
        $this->assertSame('$', $user->currencySymbol());
    }

    public function test_wallet_logs_relation_on_wallet_still_works(): void
    {
        $user = $this->createUser();
        $user->deposit(1);
        $user->deposit(2);

        $this->assertCount(2, $user->wallet->logs);
    }
}
