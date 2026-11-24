<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use App\Models\AppSetting;
use App\Services\AppJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AppApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        try {
            if (! $token) {
                throw new \RuntimeException('Bearer token is required.', 401);
            }
            $claims = app(AppJwtService::class)->decode($token);
            $user = AppUser::find((int) $claims['sub']);
            if (! $user || $user->status !== 'active') {
                throw new \RuntimeException('Account is unavailable.', 403);
            }
            if (! $user->api_token_hash || ! hash_equals($user->api_token_hash, hash('sha256', $token))) {
                throw new \RuntimeException('This session was signed out because the account logged in on another device.', 401);
            }
            $settings = AppSetting::current();
            if ($settings->server_down) {
                throw new \RuntimeException($settings->maintenance_message ?: 'The app is temporarily unavailable. Please try again later.', 503);
            }
            $request->setUserResolver(fn () => $user);
            $request->attributes->set('app_user', $user);
        } catch (Throwable $exception) {
            Log::warning('App API authentication rejected.', [
                'reason' => $exception->getMessage(),
                'ip' => $request->ip(),
            ]);
            $status = in_array($exception->getCode(), [401, 403, 503], true) ? $exception->getCode() : 401;
            $code = $status === 503
                ? 'SERVER_DOWN'
                : (str_contains($exception->getMessage(), 'another device') ? 'TOKEN_REVOKED' : 'UNAUTHENTICATED');

            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $exception->getMessage(),
            ], $status);
        }

        return $next($request);
    }
}
