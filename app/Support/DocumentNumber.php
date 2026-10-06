<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class DocumentNumber
{
    /**
     * Next free number like "{prefix}0042". Skips values already taken (including soft-deleted rows),
     * so gaps from deleted records never produce a duplicate.
     *
     * @param  class-string<Model>  $model
     */
    public static function next(string $model, string $prefix, int $pad = 4, string $column = 'number'): string
    {
        $query = fn () => $model::query()->withoutGlobalScopes();

        $seq = $query()->count() + 1;
        $candidate = $prefix.str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);

        while ($query()->where($column, $candidate)->exists()) {
            $seq++;
            $candidate = $prefix.str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
