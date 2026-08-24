<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_MOBILE = 'mobile_banking';

    protected $fillable = [
        'number', 'shop_id', 'invoice_id', 'amount', 'method', 'reference',
        'status', 'paid_at', 'verified_at', 'verified_by', 'notes',
        'rejection_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending verification',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK => 'Bank transfer',
            self::METHOD_CHEQUE => 'Cheque',
            self::METHOD_MOBILE => 'Mobile banking',
            default => ucfirst(str_replace('_', ' ', $this->method)),
        };
    }
}
