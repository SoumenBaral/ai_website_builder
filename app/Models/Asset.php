<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = ['project_id', 'url', 'type', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function project() { return $this->belongsTo(Project::class); }
}
