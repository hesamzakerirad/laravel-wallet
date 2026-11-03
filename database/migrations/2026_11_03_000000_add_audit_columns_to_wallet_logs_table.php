<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Every column is nullable, so existing rows and code keep working.
     */
    public function up(): void
    {
        Schema::table('wallet_logs', function (Blueprint $table) {
            $table->nullableMorphs('reference');
            $table->string('idempotency_key')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_logs', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
            $table->dropMorphs('reference');
        });
    }
};
