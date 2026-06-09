<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'description', 'thumbnail_path', 'status', 'published_url'];

    public function user() { return $this->belongsTo(User::class); }
    public function pages() { return $this->hasMany(Page::class); }
    public function homePage() { return $this->hasOne(Page::class)->where('is_home', true); }
    public function assets() { return $this->hasMany(Asset::class); }
    public function deployments() { return $this->hasMany(Deployment::class); }
    public function aiGenerations() { return $this->hasMany(AiGeneration::class); }
}
