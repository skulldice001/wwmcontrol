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
        Schema::rename('admins', 'staffs');
        Schema::table('staffs', function (Blueprint $table) {
            $table->string('role')->default('admin'); // master, admin, observer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::rename('staffs', 'admins');
    }
};
