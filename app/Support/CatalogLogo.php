<?php

namespace App\Support;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogLogo
{
    /**
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'];
    }

    public static function apply(Model $model, ?UploadedFile $file, bool $remove = false): void
    {
        if ($file) {
            self::deletePath($model->logo_path);
            $model->update([
                'logo_path' => $file->store(self::folder($model), 'public'),
            ]);

            return;
        }

        if ($remove && $model->logo_path) {
            self::deletePath($model->logo_path);
            $model->update(['logo_path' => null]);
        }
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    public static function deletePath(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private static function folder(Model $model): string
    {
        return $model instanceof Brand ? 'brands' : 'categories';
    }
}
