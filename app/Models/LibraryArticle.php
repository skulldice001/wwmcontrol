<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'category', 'content', 'content_format', 'excerpt',
        'status', 'discord_message_id', 'discord_author',
        'created_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    const CATEGORIES = [
        'character_development' => 'Character Development',
        'arena_summary'         => 'Arena Summary',
        'guild_war_experience'  => 'Guild War',
        'dungeon_summary'       => 'Dungeon Summary',
        'general'               => 'General',
    ];

    const CATEGORY_TAGS = [
        'character_development' => 'Character',
        'arena_summary'         => 'Arena',
        'guild_war_experience'  => 'Guild War',
        'dungeon_summary'       => 'Dungeon',
        'general'               => 'General',
    ];

    const CATEGORY_ICONS = [
        'character_development' => 'fas fa-user-graduate',
        'arena_summary'         => 'fas fa-trophy',
        'guild_war_experience'  => 'fas fa-fist-raised',
        'dungeon_summary'       => 'fas fa-dungeon',
        'general'               => 'fas fa-book',
    ];

    const CATEGORY_COLORS = [
        'character_development' => 'info',
        'arena_summary'         => 'success',
        'guild_war_experience'  => 'warning',
        'dungeon_summary'       => 'danger',
        'general'               => 'secondary',
    ];

    const CATEGORY_ACCENTS = [
        'character_development' => '#ff4444',
        'arena_summary'         => '#c084fc',
        'guild_war_experience'  => '#f59e0b',
        'dungeon_summary'       => '#22d3ee',
        'general'               => '#9ca3af',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function categoryTag(): string
    {
        return self::CATEGORY_TAGS[$this->category] ?? $this->category;
    }

    public function categoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'fas fa-book';
    }

    public function categoryColor(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'secondary';
    }

    public function categoryAccent(): string
    {
        return self::CATEGORY_ACCENTS[$this->category] ?? '#ff4444';
    }

    /** Short preview for cards. */
    public function preview(int $chars = 180): string
    {
        $text = $this->excerpt ?: $this->content;
        $plain = strip_tags($text);
        return mb_strlen($plain) > $chars ? mb_substr($plain, 0, $chars) . '…' : $plain;
    }

    /**
     * Render content for display.
     *
     * Three modes based on content_format column:
     *  - markdown : CommonMark rendering (new staff-authored articles via EasyMDE)
     *  - html     : Rich HTML authored by staff — returned as-is with lib-img class on img tags
     *  - plain    : Discord-imported content — img tags preserved, text escaped/transformed
     */
    public function renderedContent(): string
    {
        $format = $this->content_format ?? 'plain';
        $raw    = $this->content;

        if ($format === 'markdown') {
            $converter = new \League\CommonMark\CommonMarkConverter([
                'html_input'         => 'allow',
                'allow_unsafe_links' => false,
            ]);
            $html = $converter->convert($raw)->getContent();

            // Inject lib-img class on images
            return preg_replace_callback('/<img([^>]*)>/i', function ($m) {
                $attrs = $m[1];
                if (!str_contains($attrs, 'class=')) {
                    $attrs = ' class="lib-img" loading="lazy"' . $attrs;
                }
                return '<img' . $attrs . '>';
            }, $html);
        }

        if ($format === 'html') {
            return preg_replace_callback('/<img([^>]*)>/i', function ($m) {
                $attrs = $m[1];
                if (!str_contains($attrs, 'class=')) {
                    $attrs = ' class="lib-img" loading="lazy"' . $attrs;
                }
                return '<img' . $attrs . '>';
            }, $raw);
        }

        // plain text mode (Discord imports): split on <img> tags, escape text parts
        $parts  = preg_split('/(<img[^>]+>)/i', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
        $output = '';

        foreach ($parts as $part) {
            if (preg_match('/^<img[^>]+>$/i', $part)) {
                $part = preg_replace('/<img/i', '<img class="lib-img" loading="lazy"', $part);
                $output .= $part;
            } else {
                $text = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
                $text = str_replace('--- Hình ảnh ---', '<div class="lib-img-section-title">Hình ảnh</div>', $text);
                $text = preg_replace('/\n?---\n?/', '<hr class="lib-divider">', $text);
                $text = nl2br($text);
                $output .= $text;
            }
        }

        return $output;
    }
}
