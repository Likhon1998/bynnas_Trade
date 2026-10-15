<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'user_id', 'year', 'month', 'amount', 'bonus', 'method', 'status', 'paid_at', 'notes', 'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function total(): float
    {
        return round((float) $this->amount + (float) $this->bonus, 2);
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
