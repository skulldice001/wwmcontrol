<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\DiscordService;

class ListDiscordRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discord:list-roles {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Discord roles for a specific user';

    /**
     * Execute the console command.
     */
    public function handle(DiscordService $discordService)
    {
        $userId = $this->argument('user_id');

        $roles = $discordService->getRoles($userId);

        if (is_null($roles)) {
            $this->error("Failed to retrieve roles for user {$userId}. Check logs for details (likely 401/404 or config error).");
            return;
        }

        if (!empty($roles)) {
            $this->info("User {$userId} has the following roles:");
            foreach ($roles as $role) {
                if (is_array($role)) {
                    $this->line("- {$role['name']} ({$role['id']})");
                } else {
                    $this->line("- {$role}");
                }
            }
        } else {
            $this->info("User {$userId} has no roles.");
        }
    }
}
