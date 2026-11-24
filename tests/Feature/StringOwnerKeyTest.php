<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\Fixtures\UlidUser;
use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StringOwnerKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ulid_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
        });
    }

    public function test_wallet_keeps_a_ulid_owner_key(): void
    {
        $owner = UlidUser::create();

        $owner->deposit(10);
        $owner->withdraw(4);

        $wallet = $owner->fresh()->wallet;

        $this->assertSame($owner->id, $wallet->owner_id);
        $this->assertSame(6.0, $owner->fresh()->balance());
        $this->assertCount(2, $owner->walletLogs);
    }

    public function test_wallet_keeps_an_integer_owner_key(): void
    {
        $user = $this->createUser();

        $user->deposit(1);

        $this->assertEquals($user->id, $user->fresh()->wallet->owner_id);
    }
}
