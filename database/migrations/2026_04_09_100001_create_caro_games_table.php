<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caro_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caro_table_id')->constrained()->cascadeOnDelete();

            // Move list: [[row, col, "X"|"O"], ...]
            // Board is unlimited — no fixed size. Row/col can be any integer.
            $table->json('moves')->default('[]');

            // Bounding box of all moves (for rendering viewport)
            $table->integer('min_row')->default(0);
            $table->integer('max_row')->default(0);
            $table->integer('min_col')->default(0);
            $table->integer('max_col')->default(0);

            // Whose turn: 'X' (goes first) or 'O'
            $table->string('current_player', 1)->default('X');

            $table->unsignedSmallInteger('moves_count')->default(0);

            // null = ongoing, 'X' | 'O' = winner, 'draw' = draw
            $table->string('winner', 4)->nullable();

            // Winning cells for highlight: [[row,col], ...]
            $table->json('winning_cells')->nullable();

            // Deadline for current player's move
            $table->timestamp('turn_deadline')->nullable();

            $table->string('status', 20)->default('playing'); // playing | finished
            $table->timestamps();

            $table->index(['caro_table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caro_games');
    }
};
