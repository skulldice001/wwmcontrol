<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'category', 'content', 'excerpt',
        'status', 'discord_message_id', 'discord_author',
        'created_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    const CATEGORIES = [
        'character_development' => 'Phát triển nhân vật',
        'arena_summary'         => 'Tổng kết đấu trường',
        'guild_war_experience'  => 'Kinh nghiệm bang chiến',
        'dungeon_summary'       => 'Tổng kết hang động',
        'general'               => 'Chung',
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

    public function categoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'fas fa-book';
    }

    public function categoryColor(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'secondary';
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
     * Content may contain raw <img> tags (from import) mixed with plain text.
     * Strategy: split on <img> tags, escape text parts, keep img tags intact
     * then apply text transforms (separators, line breaks) only to text parts.
     */
    public function renderedContent(): string
    {
        $raw = $this->content;

        // Split content into alternating [text, img, text, img, ...] segments
        $parts  = preg_split('/(<img[^>]+>)/i', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
        $output = '';

        foreach ($parts as $part) {
            if (preg_match('/^<img[^>]+>$/i', $part)) {
                // Add class="lib-img" and loading="lazy" to existing img tags
                $part = preg_replace('/<img/i', '<img class="lib-img" loading="lazy"', $part);
                $output .= $part;
            } else {
                // Escape plain text, then apply transforms
                $text = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');

                // Section header
                $text = str_replace('--- Hình ảnh ---', '<div class="lib-img-section-title">Hình ảnh</div>', $text);

                // Separators (--- on its own line)
                $text = preg_replace('/\n?---\n?/', '<hr class="lib-divider">', $text);

                // Line breaks
                $text = nl2br($text);

                $output .= $text;
            }
        }

        return $output;
    }
}
