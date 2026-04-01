<?php

namespace App\Console\Commands;

use App\Models\LibraryArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LibraryImportDiscordCommand extends Command
{
    protected $signature = 'library:import-discord
        {--channel=1461845830737723597 : Discord forum channel ID}
        {--category=guild_war_experience : Default category for new drafts}
        {--min-length=30 : Skip messages shorter than this many characters}
        {--reimport : Delete existing Discord drafts and re-import all}';

    protected $description = 'Import posts from a Discord forum channel as library draft articles';

    const GUILD_ID = '1257953508217585704';

    public function handle(): int
    {
        $channelId = $this->option('channel');
        $category  = $this->option('category');
        $minLen    = (int) $this->option('min-length');
        $reimport  = $this->option('reimport');
        $token     = config('services.discord.bot_token');

        if (!$token) {
            $this->error('DISCORD_BOT_TOKEN not configured.');
            return self::FAILURE;
        }

        if (!array_key_exists($category, LibraryArticle::CATEGORIES)) {
            $this->error('Invalid category. Choices: ' . implode(', ', array_keys(LibraryArticle::CATEGORIES)));
            return self::FAILURE;
        }

        if ($reimport) {
            $deleted = LibraryArticle::whereNotNull('discord_message_id')
                ->where('status', 'draft')
                ->forceDelete();
            $this->warn("Deleted {$deleted} existing Discord drafts for re-import.");
        }

        $this->info("Fetching threads from forum channel {$channelId}...");

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

            if (LibraryArticle::where('discord_message_id', $threadId)->exists()) {
                $skipped++;
                continue;
            }

            $compiled = $this->compileThreadContent($token, $threadId, $minLen);

            if ($compiled === null) {
                $empty++;
                $this->line("  <fg=gray>~ [empty/short] {$threadName}</>");
                continue;
            }

            $authorName       = $this->resolveAuthorName($compiled['author']);
            $detectedCategory = $this->detectCategory($threadName . ' ' . $compiled['text']) ?? $category;

            // Build content: plain text + inline image HTML
            $body = $compiled['text'];
            if (!empty($compiled['images'])) {
                $imgHtml = implode("\n", array_map(
                    fn($url) => '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" style="max-width:100%;border-radius:8px;margin:8px 0;" alt="">',
                    $compiled['images']
                ));
                $body .= "\n\n" . $imgHtml;
            }

            LibraryArticle::create([
                'title'              => mb_substr($threadName ?: 'Bài nhập từ Discord', 0, 255),
                'category'           => $detectedCategory,
                'content'            => $body,
                'status'             => 'draft',
                'discord_message_id' => $threadId,
                'discord_author'     => $authorName,
            ]);

            $created++;
            $imgCount = count($compiled['images']);
            $imgNote  = $imgCount > 0 ? " <fg=cyan>[{$imgCount} ảnh]</>" : '';
            $this->line("  <fg=green>+ [{$detectedCategory}]</>{$imgNote} {$threadName}");
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

    /**
     * Fetch ALL messages from a thread and compile into one content block.
     * Collects all meaningful text parts and all image attachments.
     * Returns null if there is no meaningful content at all.
     */
    private function compileThreadContent(string $token, string $threadId, int $minLen): ?array
    {
        $opts    = ['verify' => config('services.discord.guzzle.verify', true)];
        $headers = ['Authorization' => "Bot {$token}"];

        // Paginate through all messages (max 100 per request, newest-first)
        $allMessages = [];
        $before      = null;

        do {
            $params = ['limit' => 100];
            if ($before) $params['before'] = $before;

            $res = Http::withHeaders($headers)->withOptions($opts)
                ->get("https://discord.com/api/v10/channels/{$threadId}/messages", $params);

            if (!$res->successful()) break;

            $batch = $res->json();
            if (empty($batch)) break;

            $allMessages = array_merge($allMessages, $batch);
            $before      = end($batch)['id'] ?? null;
        } while (count($batch) === 100);

        if (empty($allMessages)) return null;

        // Reverse to chronological order (oldest first)
        $allMessages = array_reverse($allMessages);

        $textParts   = [];
        $imageUrls   = [];
        $firstAuthor = [];

        foreach ($allMessages as $idx => $msg) {
            if ($idx === 0 && !empty($msg['author'])) {
                $firstAuthor = $msg['author'];
            }

            // Clean Discord formatting tokens
            $text = trim($msg['content'] ?? '');
            $text = preg_replace('/<@!?\d+>/', '[thành viên]', $text);
            $text = preg_replace('/<#\d+>/', '[kênh]', $text);
            $text = preg_replace('/<a?:[\w]+:\d+>/', '', $text);
            $text = trim($text);

            if (mb_strlen($text) >= $minLen) {
                $textParts[] = $text;
            }

            // Collect image attachments
            foreach ($msg['attachments'] ?? [] as $att) {
                $ct  = $att['content_type'] ?? '';
                $fn  = $att['filename'] ?? '';
                if (str_starts_with($ct, 'image/') || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fn)) {
                    $imageUrls[] = $att['proxy_url'] ?? $att['url'];
                }
            }

            // Collect images from embeds
            foreach ($msg['embeds'] ?? [] as $embed) {
                if (!empty($embed['image']['proxy_url'])) {
                    $imageUrls[] = $embed['image']['proxy_url'];
                } elseif (!empty($embed['image']['url'])) {
                    $imageUrls[] = $embed['image']['url'];
                }
                if (!empty($embed['thumbnail']['proxy_url'])) {
                    $imageUrls[] = $embed['thumbnail']['proxy_url'];
                }
            }
        }

        $imageUrls = array_values(array_unique($imageUrls));

        // Skip threads with no text and no images
        if (empty($textParts) && empty($imageUrls)) return null;

        return [
            'text'   => implode("\n\n", $textParts),
            'author' => $firstAuthor,
            'images' => $imageUrls,
        ];
    }

    private function resolveAuthorName(array $author): string
    {
        return $author['global_name'] ?? $author['username'] ?? 'Unknown';
    }

    private function detectCategory(string $text): ?string
    {
        $lower = mb_strtolower($text);

        $patterns = [
            'guild_war_experience'  => ['bang chiến', 'guild war', 'gw ', 'công thành', 'liên minh', 'chiến trường'],
            'arena_summary'         => ['đấu trường', 'arena', 'pvp', 'rank', 'bảng xếp hạng', 'mùa giải', 'thi đấu', 'season'],
            'dungeon_summary'       => ['hang động', 'dungeon', 'boss', 'raid', 'instance', 'map', 'mech', 'cơ chế'],
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
