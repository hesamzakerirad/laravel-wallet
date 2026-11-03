<?php

namespace HesamRad\LaravelWallet\Contracts;

/**
 * Optional contract for the class set in the `wallet.formatter` config.
 *
 * Any invokable class with this signature works; implementing the interface only documents it.
 */
interface FormatsAmount
{
    /**
     * Format an amount for display, e.g. "$1,234.50".
     *
     * @param int|float $amount The amount as stored (cents in integer mode).
     * @param string $currency The configured currency code, e.g. "USD".
     * @return string
     */
    public function __invoke(int|float $amount, string $currency): string;
}
