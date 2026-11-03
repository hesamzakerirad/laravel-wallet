<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_the_additive_migration_adds_nullable_columns(): void
    {
        foreach (['reference_type', 'reference_id', 'idempotency_key', 'balance_after'] as $column) {
            $this->assertTrue(Schema::hasColumn('wallet_logs', $column), "Missing column {$column}.");
        }

        $indexes = collect(Schema::getIndexes('wallet_logs'));

        $this->assertTrue($indexes->contains(fn ($index) => $index['columns'] === ['idempotency_key'] && $index['unique']));
        $this->assertTrue($indexes->contains(fn ($index) => $index['columns'] === ['reference_type', 'reference_id']));
    }

    public function test_the_additive_migration_can_be_rolled_back(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/2026_11_03_000000_add_audit_columns_to_wallet_logs_table.php';

        $migration->down();

        $this->assertSame(
            ['id', 'wallet_id', 'amount', 'action', 'description', 'meta', 'created_at', 'updated_at'],
            Schema::getColumnListing('wallet_logs'),
        );

        $migration->up();

        $this->assertTrue(Schema::hasColumn('wallet_logs', 'balance_after'));
    }
}
