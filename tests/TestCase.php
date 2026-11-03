<?php

namespace HesamRad\LaravelWallet\Tests;

use HesamRad\LaravelWallet\LaravelWalletServiceProvider;
use HesamRad\LaravelWallet\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [LaravelWalletServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createFixtureTables();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function createFixtureTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    protected function createUser(): User
    {
        return User::create(['name' => 'Test']);
    }
}
