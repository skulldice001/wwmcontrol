<?php

namespace App\Console\Commands;

use App\Models\LibraryArticle;
use App\Services\DiscordService;
use Illuminate\Console\Command;

class LibraryImportDiscordCommand extends Command
{
    protected $signature = 'library:import-discord
        {--channel=1461845830737723597 : Discord channel ID to import from}
        {--limit=100 : Number of messages to fetch (max 100 per call)}
        {--category=general : Default category for new drafts}
        {--before= : Fetch messages before this Discord message ID (for pagination)}
        {--min-length=80 : Skip messages shorter than this many characters}';

    protected $description = 'Import messages from a Discord channel as library draft articles';

    public function handle(DiscordService $discord): int
    {
        $channelId  = $this->option('channel');
        $limit      = (int) $this->option('limit');
        $category   = $this->option('category');
        $before     = $this->option('before') ?: null;
        $minLength  = (int) $this->option('min-length');

        if (!array_key_exists($category, LibraryArticle::CATEGORIES)) {
            $this->error('Invalid category. Choices: ' . implode(', ', array_keys(LibraryArticle::CATEGORIES)));
            return self::FAILURE;
        }

        $this->info("Fetching up to {$limit} messages from channel {$channelId}...");

        $messages = $discord->getChannelMessages($channelId, $limit, $before);

        if ($messages === null) {
            $this->error('Failed to fetch messages. Check DISCORD_BOT_TOKEN and channel permissions.');
            return self::FAILURE;
        }

        $this->info('Fetched ' . count($messages) . ' messages.');

        $created  = 0;
        $skipped  = 0;
        $tooShort = 0;

        foreach ($messages as $msg) {
            $content = trim($msg['content'] ?? '');

            // Skip empty, very short, or bot messages
            if (mb_strlen($content) < $minLength) {
                $tooShort++;
                continue;
            }

            // Skip messages that are only mentions/commands/links
            $strippedContent = preg_replace('/<[^>]+>/', '', $content); // remove Discord mentions/channels
            if (mb_strlen(trim($strippedContent)) < 30) {
                $tooShort++;
                continue;
            }

            $messageId = (string) $msg['id'];

            // Skip already imported
            if (LibraryArticle::where('discord_message_id', $messageId)->exists()) {
                $skipped++;
                continue;
            }

            // Extract title from first line (max 200 chars)
            $lines = explode("\n", $content);
            $firstLine = trim($lines[0]);
            // Remove Discord formatting markers from title
            $title = preg_replace('/^[#*_>\-`]+\s*/', '', $firstLine);
            $title = mb_substr($title ?: 'Bài nhập từ Discord', 0, 200);

            // Clean content: remove Discord mentions, channel links, custom emoji
            $cleanContent = preg_replace('/<@!?\d+>/', '[thành viên]', $content);
            $cleanContent = preg_replace('/<#\d+>/', '[kênh]', $cleanContent);
            $cleanContent = preg_replace('/<a?:\w+:\d+>/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $author = $msg['author']['global_name']
                   ?? $msg['author']['username']
                   ?? 'Unknown';

            // Auto-detect category by keywords
            $detectedCategory = $this->detectCategory($cleanContent) ?? $category;

            LibraryArticle::create([
                'title'              => $title,
                'category'           => $detectedCategory,
                'content'            => $cleanContent,
                'status'             => 'draft',
                'discord_message_id' => $messageId,
                'discord_author'     => $author,
            ]);

            $created++;
            $this->line("  + [{$detectedCategory}] {$title}");
        }

        $this->newLine();
        $this->info("Done. Created: {$created} | Already imported: {$skipped} | Too short: {$tooShort}");

        if (count($messages) === $limit && $created > 0) {
            $oldest = end($messages);
            $this->comment("To fetch older messages, run with: --before={$oldest['id']}");
        }

        return self::SUCCESS;
    }

    private function detectCategory(string $content): ?string
    {
        $lower = mb_strtolower($content);

        $patterns = [
            'guild_war_experience'  => ['bang chiến', 'guild war', 'gw ', 'guild chiến', 'công thành', 'giải đấu bang'],
            'arena_summary'         => ['đấu trường', 'arena', 'pvp', 'rank', 'bảng xếp hạng', 'mùa giải', 'thi đấu'],
            'dungeon_summary'       => ['hang động', 'dungeon', 'boss', 'raid', 'instance', 'bản đồ hầm'],
            'character_development' => ['nội công', 'kỹ năng', 'build', 'trang bị', 'nhân vật', 'thăng cấp', 'hướng dẫn build'],
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
