<?php

namespace Platform\CmsConnector\Support;

use Firebase\JWT\JWT;

class SignatureVerifier
{
    public function sign(array $payload, string $secret): string { return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret); }
    public function verify(array $payload, string $signature, string $secret): bool { return hash_equals($this->sign($payload, $secret), $signature); }
    public function signJwt(array $payload, string $secret): string { return JWT::encode($payload, $secret, 'HS256'); }
    public function verifyJwt(string $token, string $secret): object { return JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256')); }
}
