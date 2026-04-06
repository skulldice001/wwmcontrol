<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_articles', function (Blueprint $table) {
            $table->string('content_format', 20)->default('plain')->after('content');
        });

        // Auto-detect existing rich HTML articles
        DB::statement("
            UPDATE library_articles
            SET content_format = 'html'
            WHERE content ~ '<(div|h[1-6]|ul|ol|li|table|blockquote|section|p)'
        ");
    }

    public function down(): void
    {
        Schema::table('library_articles', function (Blueprint $table) {
            $table->dropColumn('content_format');
        });
    }
};
