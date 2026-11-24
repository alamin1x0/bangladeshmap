<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Minimal Firebase Cloud Messaging (HTTP v1) client.
 *
 * Auth: a JWT signed with the service-account private key (RS256) is exchanged for a
 * 1-hour OAuth access token, which is cached (encrypted) for 55 minutes.
 * The private key is only read from the credentials file on the server; it is never logged or sent to the browser.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const CONCURRENCY = 50;

    private array $credentials;

    public function __construct()
    {
        $path = self::credentialsPath();

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Firebase credentials file not found or not readable. Check FIREBASE_CREDENTIALS in .env.');
        }

        $json = json_decode((string) file_get_contents($path), true);

        foreach (['project_id', 'client_email', 'private_key', 'token_uri'] as $key) {
            if (empty($json[$key])) {
                throw new RuntimeException("Firebase credentials file is missing \"{$key}\".");
            }
        }

        $this->credentials = $json;
    }

    public static function credentialsPath(): string
    {
        $path = (string) config('services.firebase.credentials');

        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public static function isConfigured(): bool
    {
        $path = self::credentialsPath();

        return is_file($path) && is_readable($path);
    }

    public function projectId(): string
    {
        return $this->credentials['project_id'];
    }

    /**
     * Send one notification to many device tokens.
     *
     * @param  array<string|int, string>  $tokens  key => FCM token (key is echoed back in the results)
     * @param  array{title:string, body:string, image?:?string, link?:?string, data?:array}  $message
     * @return array<string|int, array{ok:bool, message_id:?string, error:?string, unregistered:bool}>
     */
    public function sendToTokens(array $tokens, array $message): array
    {
        $results = [];

        foreach (array_chunk($tokens, self::CONCURRENCY, true) as $chunk) {
            $chunkResults = $this->sendChunk($chunk, $message, $this->accessToken());

            // Access token rejected (revoked / expired early): refresh once and retry those messages.
            $retry = array_filter($chunkResults, fn ($r) => $r['auth_error']);
            if ($retry) {
                Cache::forget($this->cacheKey());
                $chunkResults = array_replace($chunkResults, $this->sendChunk(array_intersect_key($chunk, $retry), $message, $this->accessToken()));
            }

            foreach ($chunkResults as $key => $result) {
                unset($result['auth_error']);
                $results[$key] = $result;
            }
        }

        return $results;
    }

    private function sendChunk(array $chunk, array $message, string $accessToken): array
    {
        $url = 'https://fcm.googleapis.com/v1/projects/'.rawurlencode($this->projectId()).'/messages:send';

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn ($key) => $pool->as((string) $key)
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(20)
                ->post($url, ['message' => $this->buildMessage($chunk[$key], $message)]),
            array_keys($chunk),
        ));

        $results = [];
        foreach (array_keys($chunk) as $key) {
            $results[$key] = $this->interpret($responses[(string) $key] ?? null);
        }

        return $results;
    }

    private function buildMessage(string $token, array $message): array
    {
        $data = array_map('strval', array_filter($message['data'] ?? [], fn ($v) => $v !== null && $v !== ''));
        if (! empty($message['link'])) {
            $data['link'] = $message['link'];
        }

        $notification = ['title' => $message['title'], 'body' => $message['body']];
        if (! empty($message['image'])) {
            $notification['image'] = $message['image'];
        }

        $payload = [
            'token' => $token,
            'notification' => $notification,
            'android' => [
                'priority' => 'high',
                'notification' => ['sound' => 'default'],
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10'],
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ];

        if (! empty($message['image'])) {
            $payload['apns']['payload']['aps']['mutable-content'] = 1;
            $payload['apns']['fcm_options'] = ['image' => $message['image']];
        }

        if ($data) {
            $payload['data'] = $data;
        }

        return $payload;
    }

    private function interpret(mixed $response): array
    {
        if (! $response instanceof Response) {
            $error = $response instanceof Throwable ? $response->getMessage() : 'No response from Firebase';

            return ['ok' => false, 'message_id' => null, 'error' => mb_substr('Network error: '.$error, 0, 250), 'unregistered' => false, 'auth_error' => false];
        }

        if ($response->successful()) {
            return ['ok' => true, 'message_id' => $response->json('name'), 'error' => null, 'unregistered' => false, 'auth_error' => false];
        }

        $status = (string) $response->json('error.status');
        $codes = collect($response->json('error.details', []))->pluck('errorCode')->filter()->all();
        $unregistered = in_array('UNREGISTERED', $codes, true) || ($response->status() === 404 && $status === 'NOT_FOUND');

        $error = match (true) {
            $unregistered => 'Token no longer valid (app uninstalled or token expired)',
            in_array('INVALID_ARGUMENT', $codes, true) || $status === 'INVALID_ARGUMENT' => 'Invalid token or message: '.$response->json('error.message'),
            in_array('SENDER_ID_MISMATCH', $codes, true) => 'Token belongs to a different Firebase project',
            in_array('QUOTA_EXCEEDED', $codes, true) => 'Firebase quota exceeded, try again later',
            default => trim(($status ?: 'HTTP '.$response->status()).': '.$response->json('error.message')),
        };

        return [
            'ok' => false,
            'message_id' => null,
            'error' => mb_substr($error, 0, 250),
            'unregistered' => $unregistered,
            'auth_error' => $response->status() === 401,
        ];
    }

    private function cacheKey(): string
    {
        return 'fcm:access-token:'.sha1($this->credentials['client_email']);
    }

    private function accessToken(): string
    {
        $cached = Cache::get($this->cacheKey());
        if ($cached) {
            try {
                return Crypt::decryptString($cached);
            } catch (Throwable) {
                Cache::forget($this->cacheKey());
            }
        }

        $now = time();
        $jwt = $this->signJwt([
            'iss' => $this->credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $this->credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $response = Http::asForm()->timeout(20)->post($this->credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! $token) {
            throw new RuntimeException('Could not sign in to Firebase: '.($response->json('error_description') ?: $response->json('error') ?: 'HTTP '.$response->status()));
        }

        $ttl = max(60, (int) $response->json('expires_in', 3600) - 300);
        Cache::put($this->cacheKey(), Crypt::encryptString($token), $ttl);

        return $token;
    }

    private function signJwt(array $claims): string
    {
        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $input = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);

        $key = openssl_pkey_get_private($this->credentials['private_key']);
        if ($key === false || ! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Firebase private key could not be used for signing.');
        }

        return $input.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
