<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commission extends Model
{
    use SoftDeletes;

    public const TYPE_COLLECTION = 'collection';

    public const TYPE_TARGET_BONUS = 'target_bonus';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const STATUS_ACCRUED = 'accrued';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'number', 'salesman_id', 'sales_target_id', 'payment_id', 'order_id', 'invoice_id',
        'type', 'year', 'month', 'base_amount', 'rate_percent', 'commission_amount',
        'status', 'notes', 'approved_at', 'approved_by', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'rate_percent' => 'decimal:4',
            'commission_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_COLLECTION => 'Collection',
            self::TYPE_TARGET_BONUS => 'Target bonus',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACCRUED => 'Accrued',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
