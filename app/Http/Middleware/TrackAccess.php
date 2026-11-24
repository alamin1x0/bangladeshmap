<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackAccess
{
    /** Requests that are noise in an access log (health checks, image streams). */
    private const IGNORED_PATHS = ['up', 'location-sets/*/image', 'app-users/*/photo', 'locations/*/*/image', 'n-img/*', 'brand/*', 'favicon.ico', 'apple-touch-icon.png', 'site.webmanifest', 'robots.txt'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Runs after the response has been sent, so logging never slows down the page.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($request->is(...self::IGNORED_PATHS)) {
            return;
        }

        $userAgent = Str::limit($request->userAgent() ?? '', 1000, '');

        try {
            AccessLog::create([
                'admin_user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 2000, ''),
                'referer' => Str::limit((string) $request->headers->get('referer'), 2000, '') ?: null,
                'user_agent' => $userAgent,
                'browser' => $this->browser($userAgent),
                'platform' => $this->platform($userAgent),
                'device' => $this->device($userAgent),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Other',
        };
    }

    private function platform(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Other',
        };
    }

    private function device(string $userAgent): string
    {
        return str_contains($userAgent, 'Mobile') ? 'Mobile' : 'Desktop';
    }
}
