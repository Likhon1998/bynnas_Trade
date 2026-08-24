<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTarget extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'salesman_id', 'territory_id', 'year', 'month', 'target_amount',
        'achieved_amount', 'collected_amount', 'status', 'target_met',
        'closed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'achieved_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'target_met' => 'boolean',
            'closed_at' => 'datetime',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function achievementPercent(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return round(((float) $this->achieved_amount / (float) $this->target_amount) * 100, 1);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_CLOSED => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
