<?php

namespace Ludelix\Security\Hashing;

/**
 * Bcrypt Hasher
 * 
 * Secure password hashing using bcrypt
 */
class BcryptHasher
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'rounds' => 12,
            'verify_algo' => true
        ], $config);
    }

    /**
     * Hash password
     */
    public function hash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => $this->config['rounds']
        ]);

        if ($hash === false) {
            throw new \RuntimeException('Bcrypt hashing failed');
        }

        return $hash;
    }

    /**
     * Verify password
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if hash needs rehashing
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => $this->config['rounds']
        ]);
    }

    /**
     * Get hash info
     */
    public function info(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * Hash with salt
     */
    public function hashWithSalt(string $password, string $salt): string
    {
        return hash('sha256', $password . $salt);
    }

    /**
     * Generate salt
     */
    public function generateSalt(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Secure compare
     */
    public function compare(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Hash API key
     */
    public function hashApiKey(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Generate secure token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash with pepper
     */
    public function hashWithPepper(string $password, string $pepper): string
    {
        return $this->hash($password . $pepper);
    }

    /**
     * Verify with pepper
     */
    public function verifyWithPepper(string $password, string $hash, string $pepper): bool
    {
        return $this->verify($password . $pepper, $hash);
    }

    /**
     * Time-safe string comparison
     */
    public function timingSafeEquals(string $a, string $b): bool
    {
        if (strlen($a) !== strlen($b)) {
            return false;
        }

        return hash_equals($a, $b);
    }

    /**
     * Generate PBKDF2 hash
     */
    public function pbkdf2(string $password, string $salt, int $iterations = 10000, int $length = 32): string
    {
        return hash_pbkdf2('sha256', $password, $salt, $iterations, $length);
    }

    /**
     * Generate Argon2 hash
     */
    public function argon2(string $password): string
    {
        if (!defined('PASSWORD_ARGON2I')) {
            throw new \RuntimeException('Argon2 not supported');
        }

        $hash = password_hash($password, PASSWORD_ARGON2I);

        if ($hash === false) {
            throw new \RuntimeException('Argon2 hashing failed');
        }

        return $hash;
    }

    /**
     * Verify Argon2 hash
     */
    public function verifyArgon2(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}