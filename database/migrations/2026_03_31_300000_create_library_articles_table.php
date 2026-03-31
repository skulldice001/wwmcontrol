<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category'); // character_development | arena_summary | guild_war_experience | dungeon_summary | general
            $table->text('content');
            $table->string('excerpt', 500)->nullable();
            $table->string('status')->default('draft'); // draft | published
            $table->string('discord_message_id')->nullable()->unique();
            $table->string('discord_author')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staffs')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_articles');
    }
};
