<?php

namespace Ludelix\Security\Encryption;

/**
 * Encrypter
 * 
 * Handles encryption and decryption of data
 */
class Encrypter
{
    protected string $key;
    protected string $cipher;

    public function __construct(string $key, string $cipher = 'aes-256-cbc')
    {
        $this->key = $key;
        $this->cipher = $cipher;
        
        if (!in_array($cipher, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException("Unsupported cipher: {$cipher}");
        }
    }

    /**
     * Encrypt data
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        
        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        $payload = [
            'iv' => base64_encode($iv),
            'value' => $encrypted,
            'mac' => $this->hash($iv . $encrypted)
        ];

        return base64_encode(json_encode($payload));
    }

    /**
     * Decrypt data
     */
    public function decrypt(string $encrypted): string
    {
        $payload = json_decode(base64_decode($encrypted), true);
        
        if (!$this->validPayload($payload)) {
            throw new \RuntimeException('Invalid payload');
        }

        $iv = base64_decode($payload['iv']);
        
        // Verify MAC
        if (!hash_equals($this->hash($iv . $payload['value']), $payload['mac'])) {
            throw new \RuntimeException('MAC verification failed');
        }

        $decrypted = openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv);
        
        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Encrypt array/object
     */
    public function encryptArray(array $data): string
    {
        return $this->encrypt(json_encode($data));
    }

    /**
     * Decrypt to array
     */
    public function decryptArray(string $encrypted): array
    {
        $decrypted = $this->decrypt($encrypted);
        $data = json_decode($decrypted, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON data');
        }

        return $data;
    }

    /**
     * Generate encryption key
     */
    public static function generateKey(string $cipher = 'aes-256-cbc'): string
    {
        $length = match($cipher) {
            'aes-128-cbc' => 16,
            'aes-256-cbc' => 32,
            default => 32
        };

        return random_bytes($length);
    }

    /**
     * Validate payload structure
     */
    protected function validPayload(mixed $payload): bool
    {
        return is_array($payload) && 
               isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /**
     * Generate HMAC hash
     */
    protected function hash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->key);
    }

    /**
     * Encrypt cookie value
     */
    public function encryptCookie(string $name, mixed $value): string
    {
        $payload = [
            'name' => $name,
            'value' => $value,
            'expires' => time() + 3600
        ];

        return $this->encryptArray($payload);
    }

    /**
     * Decrypt cookie value
     */
    public function decryptCookie(string $encrypted): mixed
    {
        try {
            $payload = $this->decryptArray($encrypted);
            
            if ($payload['expires'] < time()) {
                throw new \RuntimeException('Cookie expired');
            }

            return $payload['value'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt session data
     */
    public function encryptSession(array $data): string
    {
        $payload = [
            'data' => $data,
            'timestamp' => time(),
            'fingerprint' => $this->generateFingerprint()
        ];

        return $this->encryptArray($payload);
    }

    /**
     * Decrypt session data
     */
    public function decryptSession(string $encrypted): ?array
    {
        try {
            $payload = $this->decryptArray($encrypted);
            
            if (!isset($payload['data'], $payload['fingerprint'])) {
                return null;
            }

            // Verify fingerprint for session hijacking protection
            if ($payload['fingerprint'] !== $this->generateFingerprint()) {
                return null;
            }

            return $payload['data'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate session fingerprint
     */
    protected function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return hash('sha256', $userAgent . $acceptLanguage . $ip);
    }
}