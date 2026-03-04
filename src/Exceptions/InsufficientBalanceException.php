<?php

namespace HesamRad\LaravelWallet\Exceptions;

class InsufficientBalanceException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        if (empty($message)) {
            $message = __('wallet.insufficient_balance');
        }

        return parent::__construct($message, $code, $previous);
    }
}
