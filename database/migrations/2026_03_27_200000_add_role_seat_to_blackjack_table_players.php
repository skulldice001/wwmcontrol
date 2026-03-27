<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blackjack_table_players', function (Blueprint $table) {
            // 'player' = nhà con, 'dealer' = nhà cái
            $table->string('role', 10)->default('player')->after('joined_at');
            // 0 = dealer seat, 1-7 = player seats
            $table->unsignedTinyInteger('seat')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('blackjack_table_players', function (Blueprint $table) {
            $table->dropColumn(['role', 'seat']);
        });
    }
};
