<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\Fixtures\CentsFormatter;
use HesamRad\LaravelWallet\Tests\TestCase;

class FormatterTest extends TestCase
{
    public function test_without_a_formatter_amounts_are_prefixed_with_the_symbol(): void
    {
        $user = $this->createUser();
        $user->deposit(100.5);

        $this->assertSame('$100.5', $user->balanceToString());
        $this->assertSame('$7', $user->formatAmount(7));
        $this->assertSame('$0', $this->createUser()->balanceToString());
    }

    public function test_a_configured_formatter_is_used(): void
    {
        config(['wallet.formatter' => CentsFormatter::class]);

        $user = $this->createUser();
        $user->deposit(123456);

        $this->assertSame('1,234.56 USD', $user->balanceToString());
        $this->assertSame('0.99 USD', $user->formatAmount(99));
    }
}
