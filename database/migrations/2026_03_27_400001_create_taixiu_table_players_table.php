<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taixiu_table_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taixiu_table_id')->constrained('taixiu_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->unique('user_id'); // one table at a time
            $table->index('taixiu_table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taixiu_table_players');
    }
};
