<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    protected $fillable = [
        'name', 'collection_rate_percent', 'target_bonus_percent',
        'is_default', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'collection_rate_percent' => 'decimal:4',
            'target_bonus_percent' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function activeDefault(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }
}
