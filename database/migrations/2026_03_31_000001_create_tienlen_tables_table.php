<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienlen_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('name');
            $table->enum('variant', ['mien_bac', 'mien_nam'])->default('mien_nam');
            $table->unsignedInteger('entry_fee')->default(100);
            $table->enum('status', ['waiting', 'playing', 'finished'])->default('waiting');
            $table->boolean('is_ai_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienlen_tables');
    }
};
