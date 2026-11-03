<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\Fixtures\User;
use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;

class MorphMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap(['user' => User::class]);
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    public function test_repeated_deposits_reuse_one_wallet_when_a_morph_map_is_used(): void
    {
        $user = $this->createUser();

        $user->deposit(10);
        $user->deposit(5);
        $user->withdraw(3);

        $this->assertDatabaseCount('wallets', 1);
        $this->assertDatabaseHas('wallets', [
            'owner_type' => 'user',
            'owner_id' => $user->id,
            'balance' => 12,
        ]);
        $this->assertSame(12.0, $user->fresh()->balance());
        $this->assertCount(3, $user->walletLogs);
    }
}
