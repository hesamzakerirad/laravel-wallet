<?php

namespace HesamRad\LaravelWallet;

use Illuminate\Support\ServiceProvider;

class LaravelWalletServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/wallet.php', 'wallet');
        $this->loadTranslationsFrom(__DIR__.'/../lang/', 'wallet');

        $this->publishes([
            __DIR__ . '/../config/' => config_path(),
            __DIR__.'/../database/migrations/' => database_path('migrations/'),
            __DIR__.'/../lang/' => lang_path(),
        ], 'wallet');
    }
}
