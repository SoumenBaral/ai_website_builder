<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One photo in the 2,000+ stock catalog (imported by `photos:import`,
 * described by `photos:analyze`). Served to the AI resolver and the picker.
 */
class StockPhoto extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ai_tags' => 'array',
        'ai_subjects' => 'array',
        'placements' => 'array',
        'people' => 'boolean',
        'analyzed_at' => 'datetime',
    ];

    /** "px:123" — the key used for never-repeat tracking and in URLs on the page. */
    public function key(): string
    {
        return "{$this->source}:{$this->source_id}";
    }

    /** Everything searchable about this photo, lowercase, one string. */
    public function buildSearchText(): string
    {
        $parts = array_merge(
            $this->ai_tags ?? [],
            $this->ai_subjects ?? [],
            [$this->ai_description, $this->alt, $this->topic, $this->category, $this->mood],
        );

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts)))));
    }

    /** All tag-like words for this photo (AI tags first, then subjects, then the import topic). */
    public function tagList(): array
    {
        $tags = array_merge($this->ai_tags ?? [], $this->ai_subjects ?? [], [$this->topic]);

        return array_values(array_unique(array_map(fn ($t) => mb_strtolower(trim($t)), array_filter($tags))));
    }
}
