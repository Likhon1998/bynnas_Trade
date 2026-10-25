<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogEntry
{
    /**
     * Create a category/brand, or restore the same name if it was soft-deleted.
     */
    public static function createOrRestore(string $modelClass, string $name, array $attributes = []): Model
    {
        $name = trim($name);

        /** @var Model $existing */
        $existing = $modelClass::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (! $existing->trashed()) {
                throw ValidationException::withMessages([
                    'name' => 'This name is already in use.',
                ]);
            }

            $existing->restore();
            $existing->fill(array_merge(['name' => $name, 'is_active' => true], $attributes))->save();

            return $existing->fresh();
        }

        return $modelClass::query()->create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ], $attributes));
    }
}
