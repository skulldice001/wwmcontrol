<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lottery_draws', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);        // 'daily' | 'weekly'
            $table->string('status', 10)->default('open'); // 'open' | 'drawn' | 'settled'
            $table->timestamp('draw_at');      // scheduled draw time
            $table->timestamp('drawn_at')->nullable(); // actual draw time
            $table->json('winning_numbers')->nullable(); // [3, 27] or [42]
            $table->unsignedSmallInteger('pick_count'); // 2 daily, 1 weekly
            $table->unsignedSmallInteger('multiplier'); // 10 or 70
            $table->unsignedBigInteger('total_tickets')->default(0);
            $table->unsignedBigInteger('total_pot')->default(0);
            $table->unsignedBigInteger('total_payout')->default(0);
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('draw_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draws');
    }
};
