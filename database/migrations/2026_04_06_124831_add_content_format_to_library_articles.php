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

        // Auto-detect existing rich HTML articles using PHP (avoids PostgreSQL-only ~ operator)
        DB::table('library_articles')
            ->whereNull('deleted_at')
            ->select('id', 'content')
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    if (preg_match('/<(div|h[1-6]|ul|ol|li|table|blockquote|section|p)\b/i', $row->content)) {
                        DB::table('library_articles')
                            ->where('id', $row->id)
                            ->update(['content_format' => 'html']);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('library_articles', function (Blueprint $table) {
            $table->dropColumn('content_format');
        });
    }
};
