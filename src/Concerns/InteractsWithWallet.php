<?php

namespace HesamRad\LaravelWallet\Concerns;

use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Exceptions\InvalidInputException;
use HesamRad\LaravelWallet\Models\Log;
use HesamRad\LaravelWallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
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
     * Uses the `wallet.formatter` class when one is configured.
     *
     * @return string
     */
    public function balanceToString(): string
    {
        return $this->formatAmount($this->balance());
    }

    /**
     * Format any amount the same way as balanceToString().
     *
     * Uses the `wallet.formatter` class when one is configured,
     * otherwise prefixes the currency symbol (e.g., "$100.5").
     *
     * @param int|float $amount
     * @return string
     */
    public function formatAmount(int|float $amount): string
    {
        $formatter = config('wallet.formatter');

        if ($formatter) {
            return (string)app($formatter)($amount, $this->currency());
        }

        return (string)$this->currencySymbol() . $amount;
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
     * Database errors are reported and turned into a false return value.
     * Use depositOrFail() to receive the exception instead.
     *
     * @param int|float $amount The positive amount to deposit.
     * @param string|null $description Optional description for the wallet log.
     * @param array|null $meta Optional metadata array for the wallet log.
     * @param \Illuminate\Database\Eloquent\Model|null $reference Optional model this deposit belongs to (e.g. an order).
     * @param string|null $key Optional idempotency key. A repeated key returns the first log and moves no money.
     * @return bool
     * @throws InvalidInputException
     */
    public function deposit(
        int|float $amount,
        ?string $description = '',
        ?array $meta = [],
        ?Model $reference = null,
        ?string $key = null,
    ): bool {
        try {
            $this->depositOrFail($amount, $description, $meta, $reference, $key);
        } catch (InvalidInputException $e) {
            throw $e;
        } catch (\Throwable $th) {
            report($th);
            return false;
        }

        return true;
    }

    /**
     * Deposit a specified amount into the owner's wallet, throwing on any failure.
     *
     * This operation is atomic: the wallet row is created if missing and locked
     * for update inside a single DB::transaction with automatic retries (as configured).
     * It updates the in-memory model relation upon success for immediate accurate balance checks.
     *
     * @param int|float $amount The positive amount to deposit.
     * @param string|null $description Optional description for the wallet log.
     * @param array|null $meta Optional metadata array for the wallet log.
     * @param \Illuminate\Database\Eloquent\Model|null $reference Optional model this deposit belongs to (e.g. an order).
     * @param string|null $key Optional idempotency key. A repeated key returns the first log and moves no money.
     * @return \HesamRad\LaravelWallet\Models\Log The created wallet log.
     * @throws InvalidInputException|\Throwable
     */
    public function depositOrFail(
        int|float $amount,
        ?string $description = '',
        ?array $meta = [],
        ?Model $reference = null,
        ?string $key = null,
    ): Log {
        return $this->recordTransaction('deposit', $amount, $description, $meta, $reference, $key);
    }

    /**
     * Withdraw a specified amount from the owner's wallet.
     *
     * Database errors are reported and turned into a false return value.
     * Use withdrawOrFail() to receive the exception instead.
     *
     * @param int|float $amount The positive amount to withdraw.
     * @param string|null $description Optional description for the transaction log.
     * @param array|null $meta Optional metadata array for the transaction log.
     * @param \Illuminate\Database\Eloquent\Model|null $reference Optional model this withdrawal belongs to (e.g. an order).
     * @param string|null $key Optional idempotency key. A repeated key returns the first log and moves no money.
     * @return bool Returns true on success, false if a database/transaction error occurs.
     * @throws InvalidInputException|InsufficientBalanceException
     */
    public function withdraw(
        int|float $amount,
        ?string $description = '',
        ?array $meta = [],
        ?Model $reference = null,
        ?string $key = null,
    ): bool {
        try {
            $this->withdrawOrFail($amount, $description, $meta, $reference, $key);
        } catch (InvalidInputException|InsufficientBalanceException $e) {
            throw $e;
        } catch (\Throwable $th) {
            report($th);
            return false;
        }

        return true;
    }

    /**
     * Withdraw a specified amount from the owner's wallet, throwing on any failure.
     *
     * This operation is atomic: the wallet row is locked for update and the balance
     * is checked inside the same DB::transaction, so concurrent withdrawals cannot overdraw.
     * It updates the in-memory model relation upon success.
     *
     * @param int|float $amount The positive amount to withdraw.
     * @param string|null $description Optional description for the transaction log.
     * @param array|null $meta Optional metadata array for the transaction log.
     * @param \Illuminate\Database\Eloquent\Model|null $reference Optional model this withdrawal belongs to (e.g. an order).
     * @param string|null $key Optional idempotency key. A repeated key returns the first log and moves no money.
     * @return \HesamRad\LaravelWallet\Models\Log The created wallet log.
     * @throws InvalidInputException|InsufficientBalanceException|\Throwable
     */
    public function withdrawOrFail(
        int|float $amount,
        ?string $description = '',
        ?array $meta = [],
        ?Model $reference = null,
        ?string $key = null,
    ): Log {
        return $this->recordTransaction('withdraw', $amount, $description, $meta, $reference, $key);
    }

    /**
     * Move money in or out of the locked wallet and write the matching log.
     *
     * @param string $action Either "deposit" or "withdraw".
     * @return \HesamRad\LaravelWallet\Models\Log
     * @throws InvalidInputException|InsufficientBalanceException|\Throwable
     */
    protected function recordTransaction(
        string $action,
        int|float $amount,
        ?string $description,
        ?array $meta,
        ?Model $reference,
        ?string $key,
    ): Log {
        $amount = $this->normalizeWalletAmount($amount);

        [$wallet, $log] = DB::transaction(function () use ($action, $amount, $description, $meta, $reference, $key) {
            $wallet = $this->lockWallet();

            // Checked under the wallet lock, so a retried call waits for the first one
            // and then returns its log instead of moving money twice.
            if ($key !== null && $existing = Log::query()->where('idempotency_key', $key)->first()) {
                return [$wallet, $existing];
            }

            if ($action === 'withdraw') {
                if ($wallet->balance < $amount) {
                    throw new InsufficientBalanceException();
                }

                $wallet->decrement('balance', $amount);
            } else {
                $wallet->increment('balance', $amount);
            }

            // Read the stored value back so the log matches the column exactly.
            $wallet->refresh();

            $log = $wallet->logs()->create([
                'amount' => $amount,
                'action' => $action,
                'description' => $description,
                'meta' => $meta,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'idempotency_key' => $key,
                'balance_after' => $wallet->getRawOriginal('balance'),
            ]);

            return [$wallet, $log];
        }, attempts: config('wallet.retry_attempts'));

        $this->setRelation('wallet', $wallet);

        return $log;
    }

    /**
     * Validate an amount and cast it to the configured storage type.
     *
     * In integer mode the amount must be a whole number (e.g. cents).
     *
     * @param int|float $amount
     * @return int|float
     * @throws InvalidInputException
     */
    protected function normalizeWalletAmount(int|float $amount): int|float
    {
        if ($amount <= 0) {
            throw new InvalidInputException();
        }

        if (config('wallet.amounts') !== 'integer') {
            return $amount;
        }

        if (is_float($amount) && floor($amount) !== $amount) {
            throw new InvalidInputException();
        }

        return (int)$amount;
    }

    /**
     * Find or create the owner's wallet and lock its row for update.
     *
     * Must be called inside a database transaction.
     *
     * @return \HesamRad\LaravelWallet\Models\Wallet
     */
    protected function lockWallet(): Wallet
    {
        $wallet = $this->wallet()->firstOrCreate([
            'owner_type' => static::class,
            'owner_id' => $this->id,
        ]);

        return Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
    }
}
