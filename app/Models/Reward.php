<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use SoftDeletes;

    public const TYPE_TARGET_HIT = 'target_hit';

    public const TYPE_TOP_PERFORMER = 'top_performer';

    public const TYPE_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_AWARDED = 'awarded';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number', 'salesman_id', 'sales_target_id', 'type', 'title',
        'year', 'month', 'amount', 'status', 'notes',
        'awarded_at', 'awarded_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'awarded_at' => 'datetime',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class);
    }

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TARGET_HIT => 'Target hit',
            self::TYPE_TOP_PERFORMER => 'Top performer',
            self::TYPE_MANUAL => 'Manual',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_AWARDED => 'Awarded',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
