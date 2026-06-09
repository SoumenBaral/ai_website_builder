<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = ['project_id', 'provider', 'target_url', 'status', 'log'];

    public function project() { return $this->belongsTo(Project::class); }
}
