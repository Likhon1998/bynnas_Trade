<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReturn extends Model
{
    use SoftDeletes;

    protected $table = 'returns';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public const REASON_WARRANTY = 'warranty';

    public const REASON_DEFECT = 'defect';

    public const REASON_WRONG_ITEM = 'wrong_item';

    public const REASON_OTHER = 'other';

    protected $fillable = [
        'number', 'shop_id', 'order_id', 'invoice_id', 'status', 'reason_type',
        'reason', 'total', 'restock', 'credit_issued', 'approved_at', 'approved_by',
        'resolution_notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'restock' => 'boolean',
            'credit_issued' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
            default => ucfirst($this->status),
        };
    }

    public function reasonTypeLabel(): string
    {
        return match ($this->reason_type) {
            self::REASON_WARRANTY => 'Warranty',
            self::REASON_DEFECT => 'Defect',
            self::REASON_WRONG_ITEM => 'Wrong item',
            default => 'Other',
        };
    }
}
