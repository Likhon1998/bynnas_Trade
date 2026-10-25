<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Favicon
{
    /**
     * @return list<int>
     */
    public static function sizes(): array
    {
        return [16, 32, 48, 64, 180, 192];
    }

    public static function version(): string
    {
        $path = self::sourcePath();

        return (string) (@filemtime($path) ?: filemtime(public_path('images/logo.png')) ?: time());
    }

    public static function forget(): void
    {
        $dir = storage_path('app/favicons');
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*.png') ?: [] as $file) {
            @unlink($file);
        }
    }

    public static function sourcePath(): string
    {
        $logo = app(SiteContent::class)->get('general.logo');
        if (is_string($logo) && $logo !== '' && ! str_starts_with($logo, 'http://') && ! str_starts_with($logo, 'https://')) {
            $stored = Storage::disk('public')->path($logo);
            if (is_file($stored)) {
                return $stored;
            }
        }

        $logoPng = public_path('images/logo.png');
        if (is_file($logoPng)) {
            return $logoPng;
        }

        return public_path('images/favicon.png');
    }

    public function png(int $size): string
    {
        $size = in_array($size, self::sizes(), true) ? $size : 32;
        $source = self::sourcePath();
        $cache = storage_path('app/favicons/'.md5($source.self::version()).'-'.$size.'.png');

        if (is_file($cache) && filesize($cache) > 0) {
            return (string) file_get_contents($cache);
        }

        $binary = $this->render($source, $size);
        if (! is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0775, true);
        }
        file_put_contents($cache, $binary);

        return $binary;
    }

    /**
     * Single-image ICO that modern browsers accept (PNG payload).
     */
    public function ico(): string
    {
        $png = $this->png(32);
        $size = strlen($png);

        return pack('v3', 0, 1, 1)
            .pack('C4v2V2', 32, 32, 0, 0, 1, 32, $size, 22)
            .$png;
    }

    private function render(string $source, int $size): string
    {
        if (! extension_loaded('gd') || ! is_file($source)) {
            return (string) file_get_contents(is_file($source) ? $source : public_path('images/favicon.png'));
        }

        $blob = file_get_contents($source);
        $src = $blob !== false ? @imagecreatefromstring($blob) : false;
        if ($src === false) {
            return (string) file_get_contents(public_path('images/favicon.png'));
        }

        imagealphablending($src, false);
        imagesavealpha($src, true);

        [$x, $y, $w, $h] = $this->opaqueBounds($src);
        $pad = (int) max(1, min($w, $h) * 0.08);
        $x = max(0, $x - $pad);
        $y = max(0, $y - $pad);
        $w = min(imagesx($src) - $x, $w + ($pad * 2));
        $h = min(imagesy($src) - $y, $h + ($pad * 2));

        $box = max($w, $h);
        $dest = imagecreatetruecolor($size, $size);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $clear = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $size, $size, $clear);

        $scale = $size / $box;
        $dw = max(1, (int) round($w * $scale));
        $dh = max(1, (int) round($h * $scale));
        $dx = (int) floor(($size - $dw) / 2);
        $dy = (int) floor(($size - $dh) / 2);

        imagealphablending($dest, true);
        imagecopyresampled($dest, $src, $dx, $dy, $x, $y, $dw, $dh, $w, $h);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        ob_start();
        imagepng($dest, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dest);

        return $png !== '' ? $png : (string) file_get_contents(public_path('images/favicon.png'));
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}
     */
    private function opaqueBounds(\GdImage $src): array
    {
        $width = imagesx($src);
        $height = imagesy($src);
        $minX = $width;
        $minY = $height;
        $maxX = 0;
        $maxY = 0;

        for ($yy = 0; $yy < $height; $yy++) {
            for ($xx = 0; $xx < $width; $xx++) {
                $alpha = (imagecolorat($src, $xx, $yy) & 0x7F000000) >> 24;
                if ($alpha > 120) {
                    continue;
                }
                $minX = min($minX, $xx);
                $minY = min($minY, $yy);
                $maxX = max($maxX, $xx);
                $maxY = max($maxY, $yy);
            }
        }

        if ($maxX < $minX) {
            return [0, 0, $width, $height];
        }

        return [$minX, $minY, $maxX - $minX + 1, $maxY - $minY + 1];
    }
}
