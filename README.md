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

## Support

If you encounter any issues, have questions or some idea to improve the package, feel free to create an issue or reach out to me:

1. [Create a new issue](https://github.com/hesamzakerirad/laravel-wallet/issues/new)
2. [Email me](mailto:hesamzakerirad@gmail.com)

## License

This package is open-source software licensed under the MIT license.

## Credits

- [Hesam Rad](https://github.com/hesamzakerirad)
- [All Contributors](../../contributors)
