<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bingo_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('status', 10)->default('waiting'); // waiting | playing | closed
            $table->unsignedTinyInteger('current_players')->default(0);
            $table->unsignedTinyInteger('max_players')->default(9);
            $table->unsignedTinyInteger('min_players')->default(3);
            $table->unsignedBigInteger('entry_fee')->default(50);
            $table->timestamps();
        });

        Schema::create('bingo_table_players', function (Blueprint $table) {
            $table->foreignId('bingo_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->boolean('is_ready')->default(false);
            $table->primary(['bingo_table_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_table_players');
        Schema::dropIfExists('bingo_tables');
    }
};
