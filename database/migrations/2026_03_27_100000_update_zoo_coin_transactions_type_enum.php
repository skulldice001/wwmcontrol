<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
