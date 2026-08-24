<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name', 'email', 'subject', 'message', 'status',
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'New',
            self::STATUS_READ => 'Read',
            self::STATUS_CLOSED => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
