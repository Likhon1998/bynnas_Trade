<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesmanProfile extends Model
{
    protected $fillable = [
        'user_id', 'employee_code', 'territory_id', 'monthly_target',
        'joined_at', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_target' => 'decimal:2',
            'joined_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
