<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'body', 'send_to_all', 'is_active', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'send_to_all' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesmen(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_salesman')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function isVisibleTo(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return false;
        }

        if ($this->send_to_all) {
            return true;
        }

        return $this->salesmen()->where('users.id', $user->id)->exists();
    }
}
