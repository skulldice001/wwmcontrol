<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL keeps a CHECK constraint when enum() was used — drop it first
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE zoo_coin_transactions DROP CONSTRAINT IF EXISTS zoo_coin_transactions_type_check');
        }

        Schema::table('zoo_coin_transactions', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('zoo_coin_transactions', function (Blueprint $table) {
            $table->enum('type', ['add', 'deduct', 'transfer_in', 'transfer_out', 'daily_bonus'])->change();
        });
    }
};
