<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lottery_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_draw_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('picked_number'); // 1-45
            $table->unsignedBigInteger('bet_amount');
            $table->boolean('is_winner')->nullable();       // null until settled
            $table->unsignedBigInteger('payout')->default(0);
            $table->timestamps();

            $table->index(['lottery_draw_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_tickets');
    }
};
