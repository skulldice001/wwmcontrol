<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bingo_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_table_id')->constrained()->cascadeOnDelete();
            $table->json('state');
            $table->timestamps();
            $table->index('bingo_table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_games');
    }
};
