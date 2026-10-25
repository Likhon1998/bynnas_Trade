<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Favicon;
use Illuminate\Http\Response;

class FaviconController extends Controller
{
    public function ico(Favicon $favicon): Response
    {
        return $this->image($favicon->ico(), 'image/x-icon');
    }

    public function png(Favicon $favicon, int $size = 32): Response
    {
        return $this->image($favicon->png($size), 'image/png');
    }

    private function image(string $binary, string $type): Response
    {
        return response($binary, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
