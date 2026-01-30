<?php

namespace Ludelix\Core\Security;

/**
 * JwtService - High-performance JWT implementation.
 * 
 * Provides stateless token generation and validation using HMAC SHA256.
 * Zero external dependencies.
 * 
 * @package Ludelix\Core\Security
 * @author Ludelix Framework Team
 */
class JwtService
{
    /**
     * The secret key used for signing.
     *
     * @var string
     */
    protected string $secret;

    /**
     * @param string $secret Secret key from configuration.
     */
    public function __construct(string $secret = 'ludelix-secret-key-change-me')
    {
        $this->secret = $secret;
    }

    /**
     * Generate a new JWT token.
     *
     * @param array $payload Data to include in the token.
     * @param int   $expiry  Expiration time in seconds (default 1 hour).
     * @return string
     */
    public function generate(array $payload, int $expiry = 3600): string
    {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validates a JWT token and returns the payload.
     *
     * @param string $token The JWT token string.
     * @return array|null Returns payload array if valid, null otherwise.
     */
    public function validate(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify Signature
        $validSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $header . "." . $payload, $this->secret, true)
        );

        if (!hash_equals($validSignature, $signature)) {
            return null;
        }

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

        // Check Expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }

        return $decodedPayload;
    }

    /**
     * Standard Base64Url Encode (RFC 4648).
     *
     * @param string $data
     * @return string
     */
    public function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Standard Base64Url Decode (RFC 4648).
     *
     * @param string $data
     * @return string
     */
    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
