<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE zoo_coin_transactions DROP CONSTRAINT IF EXISTS zoo_coin_transactions_type_check');
        }
    }

    public function down(): void
    {
        // Cannot restore enum check without knowing all current values; no-op
    }
};
