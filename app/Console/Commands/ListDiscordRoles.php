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
            $this->error("User {$userId} has no roles (or an error occurred).");
        }
    }
}
