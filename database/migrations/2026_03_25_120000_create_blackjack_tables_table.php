<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blackjack_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('min_bet')->default(100);
            $table->unsignedBigInteger('max_bet')->default(10000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackjack_tables');
    }
};
