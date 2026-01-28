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
        Schema::table('inner_ways', function (Blueprint $table) {
            $table->string('color')->default('blue')->after('icon'); // gold, purple, blue
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inner_ways', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
