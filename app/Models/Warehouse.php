<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'city', 'address', 'phone', 'manager_id',
        'is_default', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(InventoryLedger::class);
    }

    public function fulfilments(): HasMany
    {
        return $this->hasMany(OrderFulfilment::class);
    }

    public static function defaultWarehouse(): ?self
    {
        return static::query()->where('is_default', true)->where('is_active', true)->first()
            ?? static::query()->where('is_active', true)->orderBy('id')->first();
    }
}
