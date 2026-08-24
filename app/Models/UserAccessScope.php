<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccessScope extends Model
{
    public const TYPE_GLOBAL = 'global';

    public const TYPE_TERRITORY = 'territory';

    public const TYPE_WAREHOUSE = 'warehouse';

    public const TYPE_SHOP = 'shop';

    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_id',
        'label',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return match ($this->scope_type) {
            self::TYPE_GLOBAL => 'All data',
            self::TYPE_TERRITORY => 'Territory #'.$this->scope_id,
            self::TYPE_WAREHOUSE => 'Warehouse #'.$this->scope_id,
            self::TYPE_SHOP => 'Shop #'.$this->scope_id,
            default => ucfirst($this->scope_type),
        };
    }
}
