<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['project_id', 'title', 'path', 'schema', 'is_home'];

    protected function casts(): array
    {
        return ['schema' => 'array', 'is_home' => 'boolean'];
    }

    public function project() { return $this->belongsTo(Project::class); }
    public function versions() { return $this->hasMany(PageVersion::class); }
}
