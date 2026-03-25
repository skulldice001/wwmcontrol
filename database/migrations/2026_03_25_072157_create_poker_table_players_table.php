<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_table_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poker_table_id')->constrained('poker_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();

            // One user can only sit at one table at a time (globally unique on user_id)
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_table_players');
    }
};
