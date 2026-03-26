<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blackjack_table_players', function (Blueprint $table) {
            $table->boolean('is_ready')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('blackjack_table_players', function (Blueprint $table) {
            $table->dropColumn('is_ready');
        });
    }
};
