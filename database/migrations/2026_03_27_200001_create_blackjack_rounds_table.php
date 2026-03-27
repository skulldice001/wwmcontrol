<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blackjack_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blackjack_table_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20)->default('betting');
            // Full shared game state JSON
            $table->json('state');
            // Denormalized for fast lookup — who acts next
            $table->unsignedBigInteger('current_turn_user_id')->nullable();
            $table->timestamps();

            $table->index('blackjack_table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackjack_rounds');
    }
};
