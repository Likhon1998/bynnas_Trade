<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'business_name', 'contact_name', 'email', 'phone', 'city',
        'business_type', 'message', 'status', 'admin_notes',
        'reviewed_at', 'reviewed_by', 'shop_id', 'portal_email',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW, self::STATUS_CONTACTED => 'Pending',
            self::STATUS_CONVERTED => 'Accepted',
            self::STATUS_CLOSED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_CONTACTED], true);
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }
}
