<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const PORTAL_ADMIN = 'admin';

    public const PORTAL_SHOP = 'shop';

    public const PORTAL_SALESMAN = 'salesman';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'portal',
        'last_login_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function accessScopes(): HasMany
    {
        return $this->hasMany(UserAccessScope::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryShop(): ?Shop
    {
        return $this->shops()->wherePivot('is_primary', true)->first()
            ?? $this->shops()->first();
    }

    public function salesmanProfile(): HasOne
    {
        return $this->hasOne(SalesmanProfile::class);
    }

    public function assignedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'assigned_salesman_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ShopVisit::class, 'salesman_id');
    }

    public function salesmanOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'salesman_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isSalesman(): bool
    {
        return $this->portal === self::PORTAL_SALESMAN || $this->hasRole('Salesman');
    }

    public function hasGlobalAccessScope(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->accessScopes()->where('scope_type', UserAccessScope::TYPE_GLOBAL)->exists();
    }

    public function scopeLabel(): string
    {
        if ($this->hasGlobalAccessScope()) {
            return 'All data';
        }

        $scopes = $this->accessScopes;

        if ($scopes->isEmpty()) {
            return 'No scope assigned';
        }

        return $scopes->map(fn (UserAccessScope $scope) => $scope->displayLabel())->implode(', ');
    }
}
