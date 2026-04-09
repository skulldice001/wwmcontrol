<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caro_tables', function (Blueprint $table) {
            $table->boolean('is_ai_mode')->default(false)->after('status');
            $table->string('ai_difficulty', 10)->nullable()->after('is_ai_mode'); // easy|medium|hard
        });
    }

    public function down(): void
    {
        Schema::table('caro_tables', function (Blueprint $table) {
            $table->dropColumn(['is_ai_mode', 'ai_difficulty']);
        });
    }
};
