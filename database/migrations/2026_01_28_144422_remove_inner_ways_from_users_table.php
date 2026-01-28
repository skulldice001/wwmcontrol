<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing data from JSON to new tables
        $users = DB::table('users')->whereNotNull('inner_ways')->get();
        foreach ($users as $user) {
            $innerWays = json_decode($user->inner_ways, true);
            if (is_array($innerWays)) {
                foreach ($innerWays as $name => $level) {
                    $slug = Str::slug($name);
                    $innerWayId = DB::table('inner_ways')->where('slug', $slug)->value('id');

                    if (!$innerWayId) {
                        $innerWayId = DB::table('inner_ways')->insertGetId([
                            'name' => str_replace('-', ' ', $name),
                            'slug' => $slug,
                            'icon' => $name . '.webp', // Guessing extension
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('user_inner_way')->updateOrInsert(
                        ['user_id' => $user->id, 'inner_way_id' => $innerWayId],
                        ['level' => $level, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('inner_ways');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('inner_ways')->nullable()->after('discord_avatar');
        });
    }
};
