<?php

namespace App\Models;

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
        'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CLOSED => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
