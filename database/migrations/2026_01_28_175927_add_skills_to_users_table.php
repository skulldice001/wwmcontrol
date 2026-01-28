<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('main_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->foreignId('sub_skill_id')->nullable()->constrained('skills')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('main_skill_id');
            $table->dropConstrainedForeignId('sub_skill_id');
        });
    }
};
