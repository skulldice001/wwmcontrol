<?php

namespace App\Console\Commands;

use App\Models\LibraryArticle;
use App\Services\DiscordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LibraryImportDiscordCommand extends Command
{
    protected $signature = 'library:import-discord
        {--channel=1461845830737723597 : Discord forum channel ID}
        {--category=guild_war_experience : Default category for new drafts}
        {--min-length=30 : Skip messages shorter than this many characters}';

    protected $description = 'Import posts from a Discord forum channel as library draft articles';

    const GUILD_ID = '1257953508217585704';

    public function handle(): int
    {
        $channelId = $this->option('channel');
        $category  = $this->option('category');
        $minLen    = (int) $this->option('min-length');
        $token     = config('services.discord.bot_token');

        if (!$token) {
            $this->error('DISCORD_BOT_TOKEN not configured.');
            return self::FAILURE;
        }

        if (!array_key_exists($category, LibraryArticle::CATEGORIES)) {
            $this->error('Invalid category. Choices: ' . implode(', ', array_keys(LibraryArticle::CATEGORIES)));
            return self::FAILURE;
        }

        $this->info("Fetching threads from forum channel {$channelId}...");

        // Collect all threads (active + archived)
        $threads = $this->collectThreads($token, channelId: $channelId);

        if ($threads === null) {
            $this->error('Failed to fetch threads. Check bot permissions.');
            return self::FAILURE;
        }

        $this->info('Found ' . count($threads) . ' threads total.');

        $created = 0;
        $skipped = 0;
        $empty   = 0;

        foreach ($threads as $thread) {
            $threadId   = $thread['id'];
            $threadName = trim($thread['name'] ?? '');

            // Skip already imported (use thread ID as discord_message_id)
            if (LibraryArticle::where('discord_message_id', $threadId)->exists()) {
                $skipped++;
                continue;
            }

            // Get the starter message (oldest = first) in this thread
            $content = $this->getStarterContent($token, $threadId, $minLen);

            if ($content === null) {
                $empty++;
                $this->line("  <fg=gray>~ [empty/short] {$threadName}</>");
                continue;
            }

            $author = $thread['owner_id'] ?? null;
            // Get author name from members list if possible
            $authorName = $this->resolveAuthorName($token, $content['author'] ?? []);

            $detectedCategory = $this->detectCategory($threadName . ' ' . $content['text']) ?? $category;

            LibraryArticle::create([
                'title'              => mb_substr($threadName ?: 'Bài nhập từ Discord', 0, 255),
                'category'           => $detectedCategory,
                'content'            => $content['text'],
                'status'             => 'draft',
                'discord_message_id' => $threadId,
                'discord_author'     => $authorName,
            ]);

            $created++;
            $this->line("  <fg=green>+ [{$detectedCategory}]</> {$threadName}");
        }

        $this->newLine();
        $this->info("Done. Created: {$created} | Already imported: {$skipped} | Empty/short: {$empty}");

        return self::SUCCESS;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function collectThreads(string $token, string $channelId): ?array
    {
        $headers = ['Authorization' => "Bot {$token}"];
        $opts    = ['verify' => config('services.discord.guzzle.verify', true)];

        // Active threads in the guild filtered to this channel
        $res = Http::withHeaders($headers)->withOptions($opts)
            ->get("https://discord.com/api/v10/guilds/" . self::GUILD_ID . "/threads/active");

        if (!$res->successful()) {
            $this->error("Failed to get active threads: {$res->status()} {$res->body()}");
            return null;
        }

        $active = collect($res->json()['threads'] ?? [])
            ->where('parent_id', $channelId)
            ->values()
            ->toArray();

        // Archived threads in the channel
        $archived = [];
        $before   = null;

        do {
            $params = ['limit' => 100];
            if ($before) $params['before'] = $before;

            $ares = Http::withHeaders($headers)->withOptions($opts)
                ->get("https://discord.com/api/v10/channels/{$channelId}/threads/archived/public", $params);

            if (!$ares->successful()) break;

            $batch    = $ares->json()['threads'] ?? [];
            $archived = array_merge($archived, $batch);
            $hasMore  = $ares->json()['has_more'] ?? false;
            $before   = !empty($batch) ? end($batch)['thread_metadata']['archive_timestamp'] : null;
        } while ($hasMore && $before);

        return array_merge($active, $archived);
    }

    private function getStarterContent(string $token, string $threadId, int $minLen): ?array
    {
        $opts = ['verify' => config('services.discord.guzzle.verify', true)];

        // Get messages oldest-first by fetching with high limit then reversing
        $res = Http::withHeaders(['Authorization' => "Bot {$token}"])
            ->withOptions($opts)
            ->get("https://discord.com/api/v10/channels/{$threadId}/messages", ['limit' => 100]);

        if (!$res->successful()) return null;

        $messages = $res->json();
        if (empty($messages)) return null;

        // Discord returns newest first — reverse to get oldest (starter) first
        $messages = array_reverse($messages);

        foreach ($messages as $msg) {
            $raw  = trim($msg['content'] ?? '');
            // Clean Discord formatting
            $text = preg_replace('/<@!?\d+>/', '[thành viên]', $raw);
            $text = preg_replace('/<#\d+>/', '[kênh]', $text);
            $text = preg_replace('/<a?:[\w]+:\d+>/', '', $text);
            $text = trim($text);

            if (mb_strlen($text) >= $minLen) {
                return [
                    'text'   => $text,
                    'author' => $msg['author'] ?? [],
                ];
            }
        }

        return null;
    }

    private function resolveAuthorName(string $token, array $author): string
    {
        return $author['global_name'] ?? $author['username'] ?? 'Unknown';
    }

    private function detectCategory(string $text): ?string
    {
        $lower = mb_strtolower($text);

        $patterns = [
            'guild_war_experience'  => ['bang chiến', 'guild war', 'gw ', 'công thành', 'liên minh', 'chiến trường'],
            'arena_summary'         => ['đấu trường', 'arena', 'pvp', 'rank', 'bảng xếp hạng', 'mùa giải', 'thi đấu', 'season'],
            'dungeon_summary'       => ['hang động', 'dungeon', 'boss', 'raid', 'instance', 'map', 'mech', 'cơ chế', 'boss'],
            'character_development' => ['nội công', 'kỹ năng', 'build', 'trang bị', 'nhân vật', 'thăng cấp', 'hướng dẫn build', 'skill', 'đồ', 'lv', 'level'],
        ];

        $scores = [];
        foreach ($patterns as $cat => $keywords) {
            $scores[$cat] = 0;
            foreach ($keywords as $kw) {
                $scores[$cat] += substr_count($lower, $kw);
            }
        }

        arsort($scores);
        $top = array_key_first($scores);

        return ($scores[$top] > 0) ? $top : null;
    }
}
