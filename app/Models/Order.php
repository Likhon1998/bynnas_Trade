<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_AUDIT = 'pending_audit';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PICKING = 'picking';

    public const STATUS_PICKED = 'picked';

    public const STATUS_PACKED = 'packed';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_SALESMAN = 'salesman';

    public const SOURCE_SHOP_PORTAL = 'shop_portal';

    public const SOURCE_ADMIN = 'admin';

    protected $fillable = [
        'number', 'shop_id', 'warehouse_id', 'invoice_id', 'salesman_id', 'visit_id', 'source', 'status',
        'subtotal', 'discount_total', 'total', 'item_count', 'notes',
        'submitted_at', 'created_by', 'audited_by', 'audited_at',
        'stock_reserved_at', 'audit_notes', 'rejection_reason',
        'credit_available_at_audit', 'credit_override', 'stock_reserved',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'credit_available_at_audit' => 'decimal:2',
            'credit_override' => 'boolean',
            'stock_reserved' => 'boolean',
            'submitted_at' => 'datetime',
            'audited_at' => 'datetime',
            'stock_reserved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function fulfilment(): HasOne
    {
        return $this->hasOne(OrderFulfilment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ShopVisit::class, 'visit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_AUDIT => 'Pending Super Admin Audit',
            self::STATUS_APPROVED => 'Approved · Stock Reserved',
            self::STATUS_PICKING => 'Picking',
            self::STATUS_PICKED => 'Picked',
            self::STATUS_PACKED => 'Packed',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function isPendingAudit(): bool
    {
        return $this->status === self::STATUS_PENDING_AUDIT;
    }

    public function canPartnerEdit(): bool
    {
        return $this->isPendingAudit() && ! $this->stock_reserved;
    }

    public function canDeleteBeforeApproval(): bool
    {
        return $this->isPendingAudit() && ! $this->stock_reserved;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->hasGlobalAccessScope()) {
            return true;
        }

        if ($user->portal === User::PORTAL_SALESMAN) {
            return $this->salesman_id === $user->id;
        }

        if ($user->portal === User::PORTAL_SHOP) {
            return $this->shop?->users()->where('users.id', $user->id)->exists() ?? false;
        }

        return $this->shop?->isAccessibleBy($user) ?? false;
    }
}
