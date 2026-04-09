<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caro_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedInteger('entry_fee')->default(0); // Zoo mỗi người trả khi vào
            $table->unsignedBigInteger('player_x_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('player_o_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('waiting'); // waiting | playing | finished
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caro_tables');
    }
};
