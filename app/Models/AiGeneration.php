<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = ['user_id', 'project_id', 'prompt', 'model', 'input_tokens', 'output_tokens', 'cost_usd', 'status'];

    public function user() { return $this->belongsTo(User::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
