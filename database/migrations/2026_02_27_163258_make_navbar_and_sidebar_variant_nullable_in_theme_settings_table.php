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
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('navbar_variant')->nullable()->change();
            $table->string('sidebar_variant')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            // Revert changes
            $table->string('navbar_variant')->nullable(false)->default('navbar-white navbar-light')->change();
            $table->string('sidebar_variant')->nullable(false)->default('sidebar-dark-primary')->change();
        });
    }
};
