# Laravel Wallet

A minimalistic wallet for any Laravel application; it's easy to install and to work with. It also logs every transaction inside storage for future monitoring.

## Installation

Install via Composer:

```bash
composer require hesamrad/laravel-wallet
```

The package will automatically register its service provider.

## Configuration

Publish the necessary files:

```bash
php artisan vendor:publish --provider="HesamRad\LaravelWallet\LaravelWalletServiceProvider"
```

This will publish the config, migrations and language files used by the wallet.

## Usage

There is not much to do after installing. You only have to add a few lines to your desired model to get started; I presume you have a `User` model that needs a wallet.

1. Have the `User` model implement `\HesamRad\LaravelWallet\Contracts\HasWallet` interface.
2. Use the `\HesamRad\LaravelWallet\Concerns\InteractsWithWallet` trait inside the `User` model.

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use HesamRad\LaravelWallet\Contracts\HasWallet;
use HesamRad\LaravelWallet\Concerns\InteractsWithWallet;

class User extends Authenticatable implements HasWallet
{
    use InteractsWithWallet;

    // The resf of the model...
}
```

This will register neccessary methods and functionalities to work with wallet.

And then run the migtations: 

```bash
php artisan migrate
```

And you're done!

## How to deposit money into the wallet?

To deposit money into the wallet, call the `deposit` method on the model:

```php
$user->deposit(
    amount: 1.25, 
    description: 'This is a test.',
    meta: [
        'key' => 'value'
    ]
);
```

This will deposit 1.25 into the user's wallet and logs the transaction inside `wallet_logs` table. (Note that the `description` and `meta` fields will be stored inside `wallet_logs` table.)

## How to withdraw money from the wallet?

To withdraw money from the wallet, call the `withdraw` method on the model:

```php
$user->withdraw(
    amount: 1.00, 
    description: 'This is another test.',
    meta: [
        'another-key' => 'another-value'
    ]
);
```

This will withdraw 1.00 from the user's wallet and logs the transaction inside `wallet_logs` table. (Note that the `description` and `meta` fields will be stored inside `wallet_logs` table.)

## How to get current balance of the wallet?

To get current balance of the wallet, call the `balance` method on the model:

```php
$user->balance();
```

## How to get wallet logs?

To get wallet logs, call `logs` relationship on the wallet:

```php
$user->wallet?->logs;
```

## Deposit or withdraw and get the exception

`deposit()` and `withdraw()` return `false` when a database error happens (the error is passed to `report()`). If you prefer to handle errors yourself, use the `OrFail` variants. They return the created `Log` and throw every exception without calling `report()`:

```php
$log = $user->depositOrFail(10.00, 'Top up');
$log = $user->withdrawOrFail(2.50, 'Purchase');
```

Both throw `InvalidInputException` for a zero or negative amount and `withdrawOrFail()` throws `InsufficientBalanceException` when the balance is too low, same as the old methods.

All four methods lock the wallet row and check the balance inside one transaction, so parallel withdrawals cannot take the balance below zero.

## Link a log to another model

Pass any Eloquent model as `reference` to store it on the log:

```php
$user->withdraw(25.00, 'Order payment', reference: $order);

$user->walletLogs()->first()->reference; // the Order
```

## Make retries safe with an idempotency key

Pass a unique `key`. When the same key comes again (for example, when a queued job retries), the first log is returned and no money moves:

```php
$user->deposit(100.00, 'Stripe payout', key: "payout-{$payout->id}");
```

Keys are unique across all wallets.

## Audit trail

Every log stores `balance_after`, the wallet balance right after that transaction. Logs written before upgrading have `null` there.

## Integer amounts

By default amounts are stored as `DECIMAL(20, 5)`. To store whole numbers instead (for example cents), set this before you run the migrations:

```php
// config/wallet.php
'amounts' => 'integer', // or env WALLET_AMOUNTS=integer
```

In integer mode the migrations create `BIGINT` columns, amounts are cast to `int`, and amounts with a fraction throw `InvalidInputException`. The migrations read this value when they run, so changing it later needs your own migration to convert existing columns.

## Format amounts

Set `wallet.formatter` to an invokable class to control how `balanceToString()` and `formatAmount()` render amounts:

```php
use HesamRad\LaravelWallet\Contracts\FormatsAmount;

class CentsFormatter implements FormatsAmount
{
    public function __invoke(int|float $amount, string $currency): string
    {
        return number_format($amount / 100, 2) . ' ' . $currency;
    }
}

// config/wallet.php
'formatter' => CentsFormatter::class,

$user->balanceToString();  // "1,234.56 USD"
$user->formatAmount(99);   // "0.99 USD"
```

When `formatter` is `null` (the default), both prefix the currency symbol, e.g. `$100.5`.

## Fresh balance

`balance()` uses the cached `wallet` relation. To read the current value from the database, for example after another process changed it, use:

```php
$user->freshBalance();
```

## Events

`HesamRad\LaravelWallet\Events\WalletDeposited` and `HesamRad\LaravelWallet\Events\WalletWithdrawn` are dispatched after the transaction commits. Each has a public `$log` property. They are not dispatched when the transaction rolls back or when a repeated idempotency key moves no money.

## List logs from the owner

`walletLogs()` is a relation on the owner, newest first, so you can paginate or eager load it directly:

```php
$logs = $user->walletLogs()->paginate(20);
```

## Upgrading from 1.0 to 1.1

Version 1.1 is backward compatible. Existing calls, return values and exceptions are unchanged, and the `HasWallet` interface is the same.

It adds one **additive migration**, `add_audit_columns_to_wallet_logs_table`. It adds these nullable columns to `wallet_logs` and changes no existing column:

- `reference_type` and `reference_id`, with an index
- `idempotency_key`, with a unique index
- `balance_after`

Publish it and migrate. Files you already published are skipped unless you pass `--force`:

```bash
php artisan vendor:publish --tag=wallet
php artisan migrate
```

The new config keys (`amounts`, `formatter`) have defaults, so your published config file keeps working without changes.

## Running the tests

```bash
composer install
vendor/bin/phpunit
```

The concurrency tests need the `pcntl` and `posix` extensions and are skipped without `pcntl`.

## Support

If you encounter any issues, have questions or some idea to improve the package, feel free to create an issue or reach out to me:

1. [Create a new issue](https://github.com/hesamzakerirad/laravel-wallet/issues/new)
2. [Email me](mailto:hesamzakerirad@gmail.com)

## License

This package is open-source software licensed under the MIT license.

## Credits

- [Hesam Rad](https://github.com/hesamzakerirad)
- [All Contributors](../../contributors)
