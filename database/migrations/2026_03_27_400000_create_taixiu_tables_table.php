<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taixiu_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedBigInteger('min_bet')->default(100);
            $table->unsignedBigInteger('max_bet')->default(10000);
            $table->unsignedInteger('max_players')->default(20);
            $table->unsignedInteger('current_players')->default(0);
            $table->string('status', 20)->default('waiting'); // waiting|playing|closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taixiu_tables');
    }
};
