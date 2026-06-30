<?php

require_once APPPATH . 'Libraries/php-jwt/src/JWT.php';
require_once APPPATH . 'Libraries/php-jwt/src/Key.php';
require_once APPPATH . 'Libraries/php-jwt/src/ExpiredException.php';
require_once APPPATH . 'Libraries/php-jwt/src/BeforeValidException.php';
require_once APPPATH . 'Libraries/php-jwt/src/SignatureInvalidException.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generate_jwt')) {
    function generate_jwt(array $payload, string $secret, int $expiry = 2592000): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expiry;

        $payload['iat'] = $issuedAt;
        $payload['exp'] = (time() + 2592000);

        return JWT::encode($payload, $secret, 'HS256');
    }
}

if (!function_exists('decode_jwt')) {
    function decode_jwt(string $token, string $secret)
    {
        $decoded_object = JWT::decode($token, new Key($secret, 'HS256'));
        $decoded_array = (array) $decoded_object;
        // print_r($decoded_array);
        // die();
 
        return JWT::decode($token, new Key($secret, 'HS256'));
    }
}

if (!function_exists('jwt_secret')) {
    /**
     * The HMAC secret used to sign/verify JWTs.
     * Read from .env (jwt.secret) so it is never hardcoded in source.
     */
    function jwt_secret(): string
    {
        $secret = getenv('jwt.secret');
        if ($secret === false || $secret === '') {
            // Fail safe: never fall back to a weak/leaked hardcoded value.
            throw new \RuntimeException('jwt.secret is not configured in .env');
        }
        return $secret;
    }
}
