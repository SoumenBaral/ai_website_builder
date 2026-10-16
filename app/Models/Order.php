<?php

namespace App\Models;

use App\Services\Shop\Price;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['new', 'confirmed', 'completed', 'cancelled'];

    protected $fillable = ['project_id', 'number', 'status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'note', 'items', 'subtotal', 'currency', 'has_unpriced', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'currency' => 'array',
            'subtotal' => 'float',
            'has_unpriced' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function formattedTotal(): ?string
    {
        if ($this->subtotal === null) {
            return null;
        }
        $c = $this->currency ?? [];

        return Price::format($this->subtotal, $c['symbol'] ?? '', $c['prefix'] ?? true, $c['space'] ?? false);
    }
}
