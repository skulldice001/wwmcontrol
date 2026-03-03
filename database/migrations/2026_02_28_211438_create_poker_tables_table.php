<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('poker_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('no_limit_holdem');
            $table->decimal('small_blind', 12, 2);
            $table->decimal('big_blind', 12, 2);
            $table->decimal('min_buy_in', 12, 2);
            $table->decimal('max_buy_in', 12, 2);
            $table->integer('current_players')->default(0);
            $table->integer('max_players')->default(9);
            $table->string('status')->default('waiting'); // waiting, playing, full
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poker_tables');
    }
};
