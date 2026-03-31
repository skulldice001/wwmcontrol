<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienlen_table_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienlen_table_id')->constrained('tienlen_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedTinyInteger('seat')->default(0);
            $table->boolean('is_ready')->default(false);
            $table->timestamp('joined_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienlen_table_players');
    }
};
