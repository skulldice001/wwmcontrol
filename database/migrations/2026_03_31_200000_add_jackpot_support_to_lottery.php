<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow 'jackpot' type and nullable draw_at
        Schema::table('lottery_draws', function (Blueprint $table) {
            $table->timestamp('draw_at')->nullable()->change();
            $table->unsignedBigInteger('ticket_price')->default(0)->after('multiplier');
        });

        // Add picked_numbers JSON column for multi-number tickets (jackpot)
        Schema::table('lottery_tickets', function (Blueprint $table) {
            $table->json('picked_numbers')->nullable()->after('picked_number');
        });
    }

    public function down(): void
    {
        Schema::table('lottery_draws', function (Blueprint $table) {
            $table->dropColumn('ticket_price');
        });
        Schema::table('lottery_tickets', function (Blueprint $table) {
            $table->dropColumn('picked_numbers');
        });
    }
};
