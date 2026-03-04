<?php

namespace HesamRad\LaravelWallet\Exceptions;

class InvalidInputException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        if (empty($message)) {
            $message = __('wallet.invalid_input');
        }

        return parent::__construct($message, $code, $previous);
    }
}
