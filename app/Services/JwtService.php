<?php
// Master Arena SaaS - Pure PHP JWT Service (HMAC-SHA256)
// Comments strictly in ASCII only.

namespace App\Services;

class JwtService
{
    private string $secret;
    private int $defaultExpiration;

    public function __construct(?string $secret = null, int $defaultExpiration = 86400)
    {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $this->secret = $secret ?? ($appConfig['jwt_secret'] ?? 'masterarena_fallback_secret_key');
        $this->defaultExpiration = $defaultExpiration ?: (int)($appConfig['jwt_expiration'] ?? 86400);
    }

    // Generate signed JWT token
    public function encode(array $payload, ?int $ttlSeconds = null): string
    {
        $ttl = $ttlSeconds ?? $this->defaultExpiration;
        $now = time();

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $claims = array_merge($payload, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ]);

        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($claims));

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    // Decode and verify JWT token signature and expiration
    public function decode(string $token): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        // Verify HMAC-SHA256 signature
        $expectedSignature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        $expectedBase64Signature = $this->base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedBase64Signature, $base64Signature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($base64Payload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        // Verify token expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Verify not before claim
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return null;
        }

        return $payload;
    }

    // Base64 URL-safe encode
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // Base64 URL-safe decode
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $data .= str_repeat('=', $padLen);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}
