<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blackjack_tables', function (Blueprint $table) {
            $table->unsignedInteger('current_players')->default(0)->after('max_bet');
            $table->unsignedInteger('max_players')->default(7)->after('current_players');
            $table->string('status')->default('waiting')->after('max_players');
            $table->boolean('is_preset')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('blackjack_tables', function (Blueprint $table) {
            $table->dropColumn(['current_players', 'max_players', 'status', 'is_preset']);
        });
    }
};
