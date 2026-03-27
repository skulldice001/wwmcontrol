<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taixiu_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('taixiu_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('message', 500);
            $table->timestamps();
            $table->index(['table_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taixiu_messages');
    }
};
