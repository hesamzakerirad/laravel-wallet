<?php

namespace HesamRad\LaravelWallet\Tests\Fixtures;

use HesamRad\LaravelWallet\Contracts\FormatsAmount;

class CentsFormatter implements FormatsAmount
{
    public function __invoke(int|float $amount, string $currency): string
    {
        return number_format($amount / 100, 2) . ' ' . $currency;
    }
}
