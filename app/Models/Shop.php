<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'code', 'name', 'owner_name', 'phone', 'email', 'address', 'city',
        'territory_id', 'price_group_id', 'assigned_salesman_id',
        'credit_limit', 'outstanding_balance', 'payment_terms_days',
        'status', 'notes', 'created_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class);
    }

    public function assignedSalesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_salesman_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ShopVisit::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function productReturns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function availableCredit(): float
    {
        return max(0, (float) $this->credit_limit - (float) $this->outstanding_balance);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending Approval',
        };
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->hasGlobalAccessScope()) {
            return true;
        }

        if ($user->portal === User::PORTAL_SHOP) {
            return $this->users()->where('users.id', $user->id)->exists();
        }

        return $user->accessScopes()
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('scope_type', UserAccessScope::TYPE_SHOP)
                        ->where('scope_id', $this->id);
                })->orWhere(function ($inner) {
                    $inner->where('scope_type', UserAccessScope::TYPE_TERRITORY)
                        ->where('scope_id', $this->territory_id);
                });
            })
            ->exists();
    }
}
