<?php

namespace App\Console\Commands;

use App\Models\LibraryArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LibraryDownloadImagesCommand extends Command
{
    protected $signature = 'library:download-images
        {--dry-run : Show what would be downloaded without saving}';

    protected $description = 'Download Discord CDN images to local server and update article content';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $saveDir = public_path('img/library');
        $token   = config('services.discord.bot_token');
        $opts    = ['verify' => config('services.discord.guzzle.verify', true)];

        if (!$token) {
            $this->error('DISCORD_BOT_TOKEN not configured.');
            return self::FAILURE;
        }

        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0755, true);
        }

        $articles        = LibraryArticle::whereNotNull('discord_message_id')->get();
        $totalDownloaded = 0;
        $totalSkipped    = 0;
        $totalFailed     = 0;

        foreach ($articles as $article) {
            $threadId = $article->discord_message_id;

            // Re-fetch messages from Discord to get fresh (non-expired) CDN URLs
            $freshUrls = $this->fetchFreshImageUrls($token, $threadId, $opts);

            if (empty($freshUrls)) {
                // No images in this thread or fetch failed
                continue;
            }

            $newContent = $article->content;
            $changed    = false;

            foreach ($freshUrls as $attachmentId => $freshUrl) {
                // Derive stable filename from the attachment ID (path segment)
                $urlPath = parse_url($freshUrl, PHP_URL_PATH) ?? '';
                $ext     = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) $ext = 'jpg';
                $filename = 'lib_' . $attachmentId . '.' . $ext;
                $filePath = $saveDir . '/' . $filename;
                $localUrl = '/img/library/' . $filename;

                // Replace any CDN URL that contains this attachment ID in the content
                $pattern = '~https://(?:media|cdn|images-ext-\d+)\.discordapp\.net/[^\s"\']*' . preg_quote($attachmentId, '~') . '[^\s"\']*~';
                if (!preg_match($pattern, $newContent)) continue;

                if (file_exists($filePath)) {
                    $totalSkipped++;
                    $newContent = preg_replace($pattern, $localUrl, $newContent);
                    $changed = true;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry] [{$article->id}] {$filename}");
                    continue;
                }

                try {
                    $response = Http::timeout(20)
                        ->withOptions(['verify' => false])
                        ->get($freshUrl);

                    if ($response->successful()) {
                        file_put_contents($filePath, $response->body());
                        $newContent = preg_replace($pattern, $localUrl, $newContent);
                        $changed = true;
                        $totalDownloaded++;
                        $this->line("  <fg=green>↓</> [{$article->id}] {$filename}");
                    } else {
                        $totalFailed++;
                        $this->line("  <fg=red>✗</> [{$article->id}] HTTP {$response->status()} {$filename}");
                    }
                } catch (\Throwable $e) {
                    $totalFailed++;
                    $this->line("  <fg=red>✗</> [{$article->id}] {$e->getMessage()}");
                }
            }

            if ($changed && !$dryRun) {
                $article->update(['content' => $newContent]);
            }
        }

        $this->newLine();
        $this->info("Downloaded: {$totalDownloaded} | Already cached: {$totalSkipped} | Failed: {$totalFailed}");

        return self::SUCCESS;
    }

    /**
     * Fetch all messages in a thread and return fresh attachment image URLs
     * keyed by their attachment ID (stable identifier).
     */
    private function fetchFreshImageUrls(string $token, string $threadId, array $opts): array
    {
        $headers     = ['Authorization' => "Bot {$token}"];
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

        $result = [];

        foreach ($allMessages as $msg) {
            foreach ($msg['attachments'] ?? [] as $att) {
                $ct  = $att['content_type'] ?? '';
                $fn  = $att['filename'] ?? '';
                if (!str_starts_with($ct, 'image/') && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fn)) continue;

                // Use attachment ID as stable key
                $attId        = $att['id'];
                $result[$attId] = $att['url']; // fresh URL from API
            }

            foreach ($msg['embeds'] ?? [] as $embed) {
                // External images in embeds don't have stable IDs — skip
                $imgUrl = $embed['image']['url'] ?? null;
                if ($imgUrl && str_contains($imgUrl, 'discordapp.net/attachments/')) {
                    // Extract attachment ID from path
                    if (preg_match('~/attachments/\d+/(\d+)/~', $imgUrl, $m)) {
                        $result[$m[1]] = $imgUrl;
                    }
                }
            }
        }

        return $result;
    }
}
