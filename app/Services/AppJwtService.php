<?php

namespace App\Services;

use App\Models\AppUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class AppJwtService
{
    public function issue(AppUser $user, array $device = []): array
    {
        $now = Carbon::now();
        $expires = $now->copy()->addMinutes(config('api.access_ttl'));
        $refreshExpires = $now->copy()->addMinutes(config('api.refresh_ttl'));
        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'jti' => (string) Str::uuid(),
            'iat' => $now->timestamp,
            'exp' => $expires->timestamp,
        ];
        $token = $this->encode($payload);
        $refreshToken = Str::random(96);

        $user->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'api_refresh_token_hash' => hash('sha256', $refreshToken),
            'api_token_issued_at' => $now,
            'api_token_expires_at' => $expires,
            'api_refresh_token_expires_at' => $refreshExpires,
            'api_device_id' => $device['device_id'] ?? null,
            'last_ip_address' => $device['ip'] ?? $user->last_ip_address,
            'platform' => $device['platform'] ?? $user->platform,
            'device_model' => $device['device_model'] ?? $user->device_model,
            'app_version' => $device['app_version'] ?? $user->app_version,
            'fcm_token' => $device['fcm_token'] ?? $user->fcm_token,
            'last_login_at' => $now,
        ])->save();

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_at' => $expires->toIso8601String(),
            'refresh_expires_at' => $refreshExpires->toIso8601String(),
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $user = AppUser::where('api_refresh_token_hash', hash('sha256', $refreshToken))->first();
        if (! $user || ! $user->api_refresh_token_expires_at || $user->api_refresh_token_expires_at->isPast()) {
            throw new RuntimeException('Refresh token is invalid or expired.');
        }
        if ($user->status !== 'active') {
            throw new RuntimeException('Account is unavailable.', 403);
        }

        return $this->issue($user, [
            'device_id' => $user->api_device_id,
            'ip' => $user->last_ip_address,
            'platform' => $user->platform,
            'device_model' => $user->device_model,
            'app_version' => $user->app_version,
            'fcm_token' => $user->fcm_token,
        ]);
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || ! hash_equals($this->signature($parts[0].'.'.$parts[1]), $parts[2])) {
            throw new RuntimeException('Invalid token signature.');
        }

        $payload = json_decode($this->decodePart($parts[1]), true);
        if (! is_array($payload) || ! isset($payload['sub'], $payload['exp'], $payload['jti']) || (int) $payload['exp'] <= time()) {
            throw new RuntimeException('Token is expired or malformed.');
        }

        return $payload;
    }

    public function revoke(AppUser $user): void
    {
        $user->forceFill([
            'api_token_hash' => null,
            'api_refresh_token_hash' => null,
            'api_token_issued_at' => null,
            'api_token_expires_at' => null,
            'api_refresh_token_expires_at' => null,
            'api_device_id' => null,
        ])->save();
    }

    public function revokeAll(): int
    {
        return AppUser::query()->whereNotNull('api_token_hash')->update([
            'api_token_hash' => null,
            'api_refresh_token_hash' => null,
            'api_token_issued_at' => null,
            'api_token_expires_at' => null,
            'api_refresh_token_expires_at' => null,
            'api_device_id' => null,
        ]);
    }

    private function encode(array $payload): string
    {
        $header = $this->encodePart(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->encodePart(json_encode($payload, JSON_THROW_ON_ERROR));

        return $header.'.'.$body.'.'.$this->signature($header.'.'.$body);
    }

    private function signature(string $data): string
    {
        return $this->encodePart(hash_hmac('sha256', $data, $this->secret(), true));
    }

    private function secret(): string
    {
        $secret = (string) config('api.jwt_secret');
        if (strlen($secret) < 32) {
            throw new RuntimeException('API_JWT_SECRET must be at least 32 characters.');
        }

        return $secret;
    }

    private function encodePart(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decodePart(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true) ?: '';
    }
}
