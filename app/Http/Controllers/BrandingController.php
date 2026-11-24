<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use App\Support\WebSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Public icon / logo / manifest / robots endpoints (no login needed, heavily cached). */
class BrandingController extends Controller
{
    private const BUILT_IN = ['favicon.svg', 'favicon.ico', 'icon-32.png', 'icon-180.png', 'icon-192.png', 'icon-512.png'];

    public function file(string $file): BinaryFileResponse
    {
        if (in_array($file, self::BUILT_IN, true)) {
            $path = resource_path('brand/'.$file);
            $maxAge = 86400;
        } else {
            $disk = Storage::disk(Branding::DISK);
            abort_unless($disk->exists(Branding::DIR.'/'.$file), 404);
            $path = $disk->path(Branding::DIR.'/'.$file);
            $maxAge = 31536000; // random names change on every upload, so they never go stale
        }

        $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            default => 'image/png',
        };

        return response()->file($path, [
            'Content-Type' => $type,
            'Cache-Control' => "public, max-age={$maxAge}".($maxAge > 86400 ? ', immutable' : ''),
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function favicon(): BinaryFileResponse
    {
        $fav = WebSettings::get('favicon');

        return $this->file($fav ? $fav.'-favicon.ico' : 'favicon.ico')
            ->setMaxAge(86400)->setPublic();
    }

    public function appleTouch(): BinaryFileResponse
    {
        $fav = WebSettings::get('favicon');

        return $this->file($fav ? $fav.'-180.png' : 'icon-180.png')->setMaxAge(86400)->setPublic();
    }

    public function manifest(): JsonResponse
    {
        $s = WebSettings::all();
        $icons = WebSettings::icons();

        return response()->json([
            'name' => $s['site_name'].' — '.$s['tagline'],
            'short_name' => $s['site_name'],
            'start_url' => route('home'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $s['theme_color'],
            'icons' => [
                ['src' => $icons['192'], 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $icons['512'], 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function robots(): Response
    {
        $body = WebSettings::get('allow_indexing') && WebSettings::get('site_status') === 'live'
            ? "User-agent: *\nAllow: /\nDisallow: /login\nDisallow: /dashboard\nDisallow: /api/\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
