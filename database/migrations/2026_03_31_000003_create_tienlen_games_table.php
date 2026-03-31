<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienlen_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienlen_table_id')->constrained('tienlen_tables')->cascadeOnDelete();
            $table->json('state'); // full game state
            $table->enum('status', ['active', 'finished'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienlen_games');
    }
};
