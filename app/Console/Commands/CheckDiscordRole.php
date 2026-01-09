<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\DiscordService;

class CheckDiscordRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discord:check-role {user_id} {role_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if a Discord user has a specific role';

    /**
     * Execute the console command.
     */
    public function handle(DiscordService $discordService)
    {
        $userId = $this->argument('user_id');
        $roleId = $this->argument('role_id');

        $hasRole = $discordService->hasRole($userId, $roleId);

        if ($hasRole) {
            $this->info("User {$userId} HAS the role.");
        } else {
            $this->error("User {$userId} does NOT have the role (or an error occurred).");
        }
    }
}
