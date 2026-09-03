<?php

use App\Models\Product;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dir = storage_path('app/public/products');
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$catalog = [
    'BT-EB-01' => [
        'label' => 'Earbuds',
        'url' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=600&q=80',
        'file' => 'bt-eb-01.jpg',
    ],
    'PW-BK-20' => [
        'label' => 'Power Bank',
        'url' => 'https://images.unsplash.com/photo-1609091839311-b65b1df7b42a?auto=format&fit=crop&w=600&q=80',
        'file' => 'pw-bk-20.jpg',
    ],
    'SM-WT-09' => [
        'label' => 'Smart Watch',
        'url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80',
        'file' => 'sm-wt-09.jpg',
    ],
    'CG-CH-33' => [
        'label' => 'Charger',
        'url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=600&q=80',
        'file' => 'cg-ch-33.jpg',
    ],
    'SP-BT-12' => [
        'label' => 'Speaker',
        'url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=600&q=80',
        'file' => 'sp-bt-12.jpg',
    ],
];

foreach ($catalog as $sku => $meta) {
    $path = $dir.DIRECTORY_SEPARATOR.$meta['file'];
    $relative = 'products/'.$meta['file'];

    $bytes = @file_get_contents($meta['url']);
    if ($bytes === false || strlen($bytes) < 1000) {
        // Fallback: generate a simple branded placeholder if download fails.
        $img = imagecreatetruecolor(600, 600);
        $bg = imagecolorallocate($img, 243, 232, 255);
        $fg = imagecolorallocate($img, 124, 58, 237);
        imagefilledrectangle($img, 0, 0, 600, 600, $bg);
        imagestring($img, 5, 220, 290, $meta['label'], $fg);
        imagejpeg($img, $path, 85);
        imagedestroy($img);
        echo "Generated placeholder for {$sku}\n";
    } else {
        file_put_contents($path, $bytes);
        echo "Downloaded {$sku}\n";
    }

    $updated = Product::query()->where('sku', $sku)->update(['image_path' => $relative]);
    echo $updated ? "Linked {$sku} -> {$relative}\n" : "No product row for {$sku}\n";
}

echo "Done.\n";
