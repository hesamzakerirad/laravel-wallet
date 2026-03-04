<?php

namespace HesamRad\LaravelWallet\Concerns;

use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Exceptions\InvalidInputException;
use HesamRad\LaravelWallet\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

trait InteractsWithWallet
{
    /**
     * Define the polymorphic relationship to the Wallet model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Retrieve the current balance of the owner's wallet.
     *
     * Note: This leverages the in-memory wallet relation if it has been loaded or updated by a deposit/withdraw call,
     * otherwise it triggers a single lazy load query.
     *
     * @return float The current wallet balance. Returns 0.0 if the wallet does not exist.
     */
    public function balance(): float
    {
        $wallet = $this->wallet;

        if (!$wallet) {
            return 0.0;
        }

        return (float)$wallet->balance;
    }

    /**
     * Retrieve the current balance formatted as a localized string
     * including the currency symbol. (e.g., "$100.50").
     *
     * @return string
     */
    public function balanceToString(): string
    {
        return (string)$this->currencySymbol() . $this->balance();
    }

    /**
     * Retrieve the default currency code configured for the application.
     *
     * @return string
     */
    public function currency(): string
    {
        return config('wallet.currency');
    }

    /**
     * Retrieves the symbol for the configured default currency.
     *
     * @return string
     */
    public function currencySymbol(): string
    {
        $currency = $this->currency();

        return config('wallet.currencies.' . $currency . '.symbol');
    }

    /**
     * Deposit a specified amount into the owner's wallet.
     *
     * This operation is atomic, using DB::transaction with automatic retries (as configured).
     * It updates the in-memory model relation upon success for immediate accurate balance checks.
     *
     * @param float $amount The positive amount to deposit.
     * @param string|null $description Optional description for the wallet log.
     * @param array|null $meta Optional metadata array for the wallet log.
     * @return bool
     * @throws InvalidInputException
     */
    public function deposit(float $amount, ?string $description = '', ?array $meta = []): bool
    {
        if ($amount <= 0) {
            throw new InvalidInputException();
        }

        $wallet = $this->wallet()->firstOrCreate([
            'owner_type' => static::class,
            'owner_id' => $this->id,
        ]);

        try {
            DB::transaction(function () use ($wallet, $amount, $description, $meta) {
                $wallet->increment('balance', $amount);

                $wallet->logs()->create([
                    'amount' => $amount,
                    'action' => 'deposit',
                    'description' => $description,
                    'meta' => $meta,
                ]);
            }, attempts: config('wallet.retry_attempts'));

            $this->setRelation('wallet', $wallet);
        } catch (\Throwable $th) {
            report($th);
            return false;
        }

        return true;
    }

    /**
     * Withdraw a specified amount from the owner's wallet.
     *
     * This operation is atomic, using DB::transaction with automatic retries.
     * It checks for sufficient balance *before* initiating the transaction.
     * It updates the in-memory model relation upon success.
     *
     * @param float $amount The positive amount to withdraw.
     * @param string|null $description Optional description for the transaction log.
     * @param array|null $meta Optional metadata array for the transaction log.
     * @return bool Returns true on success, false if a database/transaction error occurs.
     * @throws InvalidInputException|InsufficientBalanceException
     */
    public function withdraw(float $amount, ?string $description = '', ?array $meta = []): bool
    {
        if ($amount <= 0) {
            throw new InvalidInputException();
        }

        $wallet = $this->wallet()->firstOrCreate([
            'owner_type' => static::class,
            'owner_id' => $this->id,
        ]);

        if (is_null($wallet) || $wallet->balance < $amount) {
            throw new InsufficientBalanceException();
        }

        try {
            DB::transaction(function () use ($wallet, $amount, $description, $meta) {
                $wallet->decrement('balance', $amount);

                $wallet->logs()->create([
                    'amount' => $amount,
                    'action' => 'withdraw',
                    'description' => $description,
                    'meta' => $meta,
                ]);
            }, attempts: config('wallet.retry_attempts'));

            $this->setRelation('wallet', $wallet);
        } catch (\Throwable $th) {
            report($th);
            return false;
        }

        return true;
    }
}
