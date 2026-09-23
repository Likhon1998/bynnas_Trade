<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moduleLabel(): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->module));
    }

    public function actionLabel(): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->action));
    }

    /**
     * Visual tone for action badges.
     */
    public function actionTone(): string
    {
        $action = strtolower($this->action);

        return match (true) {
            str_contains($action, 'reject') || str_contains($action, 'delete') || str_contains($action, 'void') || str_contains($action, 'cancel') => 'danger',
            str_contains($action, 'approve') || str_contains($action, 'verify') || str_contains($action, 'accept') || str_contains($action, 'paid') || str_contains($action, 'deliver') => 'success',
            str_contains($action, 'login') || str_contains($action, 'logout') || str_contains($action, 'create') || str_contains($action, 'issued') || str_contains($action, 'record') || str_contains($action, 'submit') => 'info',
            str_contains($action, 'update') || str_contains($action, 'edit') || str_contains($action, 'hold') || str_contains($action, 'adjust') => 'warn',
            default => 'neutral',
        };
    }

    public function entityLabel(): ?string
    {
        if (! $this->auditable_type || ! $this->auditable_id) {
            return null;
        }

        return class_basename($this->auditable_type).' #'.$this->auditable_id;
    }

    public function hasValueDiffs(): bool
    {
        return ! empty($this->old_values) || ! empty($this->new_values);
    }

    /**
     * @return list<array{key:string, old:mixed, new:mixed}>
     */
    public function changeRows(): array
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $rows = [];

        foreach ($keys as $key) {
            $rows[] = [
                'key' => (string) $key,
                'old' => $old[$key] ?? null,
                'new' => $new[$key] ?? null,
            ];
        }

        return $rows;
    }

    public function browserHint(): ?string
    {
        if (! $this->user_agent) {
            return null;
        }

        $ua = $this->user_agent;

        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') => 'Safari',
            default => 'Browser',
        };
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
