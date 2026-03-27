<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taixiu_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taixiu_table_id')->constrained('taixiu_tables')->cascadeOnDelete();
            $table->json('state');
            $table->timestamps();
            $table->index('taixiu_table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taixiu_games');
    }
};
