<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesmanSalary extends Model
{
    protected $fillable = [
        'user_id', 'monthly_salary', 'effective_from', 'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'effective_from' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
