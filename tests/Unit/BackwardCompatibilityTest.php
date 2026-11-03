<?php

namespace HesamRad\LaravelWallet\Tests\Unit;

use HesamRad\LaravelWallet\Contracts\HasWallet;
use HesamRad\LaravelWallet\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

/**
 * Guards the public API of 1.0 so later changes stay additive.
 */
class BackwardCompatibilityTest extends TestCase
{
    public function test_the_has_wallet_contract_is_unchanged(): void
    {
        $this->assertSame([
            'wallet(): Illuminate\Database\Eloquent\Relations\MorphOne',
            'balance(): float',
            'balanceToString(): string',
            "deposit(float \$amount, ?string \$description = '', ?array \$meta = []): bool",
            "withdraw(float \$amount, ?string \$description = '', ?array \$meta = []): bool",
        ], array_map(
            fn (\ReflectionMethod $method) => $this->signature($method),
            (new \ReflectionClass(HasWallet::class))->getMethods(),
        ));
    }

    public function test_the_trait_keeps_the_old_parameters_first_and_adds_only_optional_ones(): void
    {
        foreach (['deposit', 'withdraw'] as $name) {
            $method = new \ReflectionMethod(User::class, $name);
            $parameters = $method->getParameters();

            $this->assertSame('bool', (string) $method->getReturnType());
            $this->assertSame(
                ['amount', 'description', 'meta', 'reference', 'key'],
                array_map(fn ($parameter) => $parameter->getName(), $parameters),
            );
            $this->assertSame(1, $method->getNumberOfRequiredParameters());
        }
    }

    protected function signature(\ReflectionMethod $method): string
    {
        $parameters = array_map(function (\ReflectionParameter $parameter) {
            $signature = $parameter->getType() . ' $' . $parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                $signature .= ' = ' . ($parameter->getDefaultValue() === [] ? '[]' : var_export($parameter->getDefaultValue(), true));
            }

            return $signature;
        }, $method->getParameters());

        return $method->getName() . '(' . implode(', ', $parameters) . '): ' . $method->getReturnType();
    }
}
