<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVersion extends Model
{
    protected $fillable = ['page_id', 'schema', 'label', 'created_by', 'created_at'];
    public $timestamps = false;

    protected static function booted(): void
    {
        // No updated_at on this table, so Eloquent's timestamps are off; stamp creation ourselves.
        static::creating(fn (self $version) => $version->created_at ??= now());
    }

    protected function casts(): array
    {
        return ['schema' => 'array', 'created_at' => 'datetime'];
    }

    public function page() { return $this->belongsTo(Page::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
