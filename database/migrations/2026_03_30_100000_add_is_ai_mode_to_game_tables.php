<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tables', function (Blueprint $table) {
            $table->boolean('is_ai_mode')->default(false)->after('id');
        });

        Schema::table('blackjack_tables', function (Blueprint $table) {
            $table->boolean('is_ai_mode')->default(false)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('poker_tables',    fn(Blueprint $t) => $t->dropColumn('is_ai_mode'));
        Schema::table('blackjack_tables', fn(Blueprint $t) => $t->dropColumn('is_ai_mode'));
    }
};
