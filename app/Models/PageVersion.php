<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVersion extends Model
{
    protected $fillable = ['page_id', 'schema', 'label', 'created_by'];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['schema' => 'array', 'created_at' => 'datetime'];
    }

    public function page() { return $this->belongsTo(Page::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
