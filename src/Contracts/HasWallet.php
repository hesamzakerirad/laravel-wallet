<?php

namespace HesamRad\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasWallet
{
    public function wallet(): MorphOne;

    public function balance(): float;

    public function balanceToString(): string;

    public function deposit(float $amount, ?string $description = '', ?array $meta = []): bool;

    public function withdraw(float $amount, ?string $description = '', ?array $meta = []): bool;
}
