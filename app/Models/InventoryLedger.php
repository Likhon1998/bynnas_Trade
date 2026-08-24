<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLedger extends Model
{
    public const TYPE_RECEIVE = 'receive';

    public const TYPE_ADJUST = 'adjust';

    public const TYPE_RESERVE = 'reserve';

    public const TYPE_RELEASE = 'release';

    public const TYPE_PICK = 'pick';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    protected $fillable = [
        'warehouse_id', 'product_id', 'order_id', 'type', 'qty_delta',
        'qty_on_hand_after', 'qty_reserved_after', 'reference', 'notes', 'created_by',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
