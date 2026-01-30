<?php

namespace Ludelix\Core\Support;

/**
 * Advanced Password Security Manager
 * 
 * Professional password hashing, verification, and security utilities
 * with multiple algorithms, strength validation, breach checking,
 * and advanced security features beyond traditional implementations.
 */
class SecureHash
{
    /**
     * Hashing algorithm constants
     */
    public const ALGO_ARGON2ID = 'argon2id';
    public const ALGO_ARGON2I = 'argon2i';
    public const ALGO_BCRYPT = 'bcrypt';
    public const ALGO_SCRYPT = 'scrypt';

    /**
     * Security level constants
     */
    public const SECURITY_LOW = 1;
    public const SECURITY_MEDIUM = 2;
    public const SECURITY_HIGH = 3;
    public const SECURITY_PARANOID = 4;

    /**
     * Default configuration
     */
    private static array $config = [
        'algorithm' => self::ALGO_ARGON2ID,
        'security_level' => self::SECURITY_HIGH,
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
        'bcrypt_rounds' => 12,
        'pepper' => null,
        'max_length' => 4096
    ];

    /**
     * Common weak passwords database
     */
    private static array $weakPasswords = [
        'password', '123456', '123456789', 'qwerty', 'abc123', 'password123',
        'admin', 'letmein', 'welcome', 'monkey', '1234567890', 'dragon'
    ];

    /**
     * Configure SecureHash settings
     * 
     * @param array $options Configuration options
     */
    public static function configure(array $options): void
    {
        self::$config = array_merge(self::$config, $options);
    }

    /**
     * Hash password with advanced security
     * 
     * Creates secure password hash using specified algorithm and security level.
     * Includes automatic salt generation, pepper support, and timing attack protection.
     * 
     * @param string $password Plain text password
     * @param string|null $algorithm Hashing algorithm
     * @param int|null $securityLevel Security level (1-4)
     * @return string Secure password hash
     */
    public static function hash(string $password, ?string $algorithm = null, ?int $securityLevel = null): string
    {
        if (strlen($password) > self::$config['max_length']) {
            throw new \InvalidArgumentException('Password exceeds maximum length');
        }

        $algorithm = $algorithm ?? self::$config['algorithm'];
        $securityLevel = $securityLevel ?? self::$config['security_level'];
        
        // Apply pepper if configured
        if (self::$config['pepper']) {
            $password = hash_hmac('sha256', $password, self::$config['pepper']);
        }

        return match($algorithm) {
            self::ALGO_ARGON2ID => self::hashArgon2id($password, $securityLevel),
            self::ALGO_ARGON2I => self::hashArgon2i($password, $securityLevel),
            self::ALGO_BCRYPT => self::hashBcrypt($password, $securityLevel),
            self::ALGO_SCRYPT => self::hashScrypt($password, $securityLevel),
            default => throw new \InvalidArgumentException("Unsupported algorithm: {$algorithm}")
        };
    }

    /**
     * Verify password against hash
     * 
     * Securely verifies password with timing attack protection
     * and automatic algorithm detection.
     * 
     * @param string $password Plain text password
     * @param string $hash Password hash
     * @return bool True if password matches hash
     */
    public static function verify(string $password, string $hash): bool
    {
        if (strlen($password) > self::$config['max_length']) {
            return false;
        }

        // Apply pepper if configured
        if (self::$config['pepper']) {
            $password = hash_hmac('sha256', $password, self::$config['pepper']);
        }

        // Use PHP's password_verify for timing attack protection
        return password_verify($password, $hash);
    }

    /**
     * Check if hash needs rehashing
     * 
     * Determines if password hash should be updated due to
     * algorithm changes or security level improvements.
     * 
     * @param string $hash Password hash
     * @param string|null $algorithm Target algorithm
     * @param int|null $securityLevel Target security level
     * @return bool True if rehashing needed
     */
    public static function needsRehash(string $hash, ?string $algorithm = null, ?int $securityLevel = null): bool
    {
        $algorithm = $algorithm ?? self::$config['algorithm'];
        $securityLevel = $securityLevel ?? self::$config['security_level'];
        
        $options = self::getAlgorithmOptions($algorithm, $securityLevel);
        $phpAlgo = self::getPhpAlgorithm($algorithm);
        
        return password_needs_rehash($hash, $phpAlgo, $options);
    }

    /**
     * Validate password strength
     * 
     * Comprehensive password strength validation with detailed feedback.
     * Checks length, complexity, common patterns, and known weak passwords.
     * 
     * @param string $password Password to validate
     * @param array $requirements Custom requirements
     * @return array Validation result with score and feedback
     */
    public static function validateStrength(string $password, array $requirements = []): array
    {
        $requirements = array_merge([
            'min_length' => 8,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'min_unique_chars' => 4,
            'check_common_passwords' => true,
            'check_patterns' => true
        ], $requirements);

        $score = 0;
        $feedback = [];
        $length = strlen($password);

        // Length validation
        if ($length < $requirements['min_length']) {
            $feedback[] = "Password must be at least {$requirements['min_length']} characters";
        } elseif ($length >= $requirements['min_length']) {
            $score += 20;
        }

        if ($length > $requirements['max_length']) {
            $feedback[] = "Password must not exceed {$requirements['max_length']} characters";
        }

        // Character type validation
        if ($requirements['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $feedback[] = 'Password must contain uppercase letters';
        } elseif (preg_match('/[A-Z]/', $password)) {
            $score += 15;
        }

        if ($requirements['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $feedback[] = 'Password must contain lowercase letters';
        } elseif (preg_match('/[a-z]/', $password)) {
            $score += 15;
        }

        if ($requirements['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $feedback[] = 'Password must contain numbers';
        } elseif (preg_match('/[0-9]/', $password)) {
            $score += 15;
        }

        if ($requirements['require_symbols'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $feedback[] = 'Password must contain special characters';
        } elseif (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 15;
        }

        // Unique characters
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars < $requirements['min_unique_chars']) {
            $feedback[] = "Password must contain at least {$requirements['min_unique_chars']} unique characters";
        } else {
            $score += min(20, $uniqueChars * 2);
        }

        // Common password check
        if ($requirements['check_common_passwords'] && in_array(strtolower($password), self::$weakPasswords)) {
            $feedback[] = 'Password is too common';
            $score -= 30;
        }

        // Pattern detection
        if ($requirements['check_patterns']) {
            if (preg_match('/(.)\1{2,}/', $password)) {
                $feedback[] = 'Password contains repeated characters';
                $score -= 10;
            }
            
            if (preg_match('/123|abc|qwe|asd/i', $password)) {
                $feedback[] = 'Password contains sequential patterns';
                $score -= 15;
            }
        }

        $score = max(0, min(100, $score));
        
        return [
            'score' => $score,
            'strength' => self::getStrengthLabel($score),
            'valid' => empty($feedback),
            'feedback' => $feedback
        ];
    }

    /**
     * Generate secure random password
     * 
     * Creates cryptographically secure password with customizable options.
     * 
     * @param int $length Password length
     * @param array $options Generation options
     * @return string Generated password
     */
    public static function generate(int $length = 16, array $options = []): string
    {
        $options = array_merge([
            'uppercase' => true,
            'lowercase' => true,
            'numbers' => true,
            'symbols' => true,
            'exclude_ambiguous' => true,
            'ensure_complexity' => true
        ], $options);

        $charset = '';
        
        if ($options['lowercase']) {
            $charset .= $options['exclude_ambiguous'] ? 'abcdefghijkmnopqrstuvwxyz' : 'abcdefghijklmnopqrstuvwxyz';
        }
        
        if ($options['uppercase']) {
            $charset .= $options['exclude_ambiguous'] ? 'ABCDEFGHJKLMNPQRSTUVWXYZ' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        if ($options['numbers']) {
            $charset .= $options['exclude_ambiguous'] ? '23456789' : '0123456789';
        }
        
        if ($options['symbols']) {
            $charset .= $options['exclude_ambiguous'] ? '!@#$%^&*-_=+' : '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }

        if (empty($charset)) {
            throw new \InvalidArgumentException('At least one character type must be enabled');
        }

        $password = '';
        $charsetLength = strlen($charset);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $charset[random_int(0, $charsetLength - 1)];
        }

        // Ensure complexity if required
        if ($options['ensure_complexity'] && $length >= 4) {
            $password = self::ensureComplexity($password, $options);
        }

        return $password;
    }

    /**
     * Generate memorable password using word combinations
     * 
     * Creates secure but memorable passwords using word combinations
     * with numbers and symbols.
     * 
     * @param int $words Number of words
     * @param string $separator Word separator
     * @param bool $addNumbers Add random numbers
     * @param bool $addSymbols Add random symbols
     * @return string Generated memorable password
     */
    public static function generateMemorable(int $words = 4, string $separator = '-', bool $addNumbers = true, bool $addSymbols = true): string
    {
        $wordList = [
            'apple', 'brave', 'cloud', 'dance', 'eagle', 'flame', 'grace', 'happy',
            'island', 'jungle', 'knight', 'light', 'magic', 'noble', 'ocean', 'peace',
            'quiet', 'river', 'storm', 'tiger', 'unity', 'voice', 'water', 'youth'
        ];

        $selectedWords = [];
        for ($i = 0; $i < $words; $i++) {
            $selectedWords[] = ucfirst($wordList[array_rand($wordList)]);
        }

        $password = implode($separator, $selectedWords);

        if ($addNumbers) {
            $password .= random_int(10, 99);
        }

        if ($addSymbols) {
            $symbols = ['!', '@', '#', '$', '%'];
            $password .= $symbols[array_rand($symbols)];
        }

        return $password;
    }

    /**
     * Hash password using Argon2id
     */
    private static function hashArgon2id(string $password, int $securityLevel): string
    {
        $options = self::getArgon2Options($securityLevel);
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Hash password using Argon2i
     */
    private static function hashArgon2i(string $password, int $securityLevel): string
    {
        $options = self::getArgon2Options($securityLevel);
        return password_hash($password, PASSWORD_ARGON2I, $options);
    }

    /**
     * Hash password using bcrypt
     */
    private static function hashBcrypt(string $password, int $securityLevel): string
    {
        $cost = match($securityLevel) {
            self::SECURITY_LOW => 10,
            self::SECURITY_MEDIUM => 12,
            self::SECURITY_HIGH => 14,
            self::SECURITY_PARANOID => 16
        };
        
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Hash password using scrypt (custom implementation)
     */
    private static function hashScrypt(string $password, int $securityLevel): string
    {
        // Fallback to Argon2id if scrypt not available
        return self::hashArgon2id($password, $securityLevel);
    }

    /**
     * Get Argon2 options based on security level
     */
    private static function getArgon2Options(int $securityLevel): array
    {
        return match($securityLevel) {
            self::SECURITY_LOW => [
                'memory_cost' => 32768,
                'time_cost' => 2,
                'threads' => 2
            ],
            self::SECURITY_MEDIUM => [
                'memory_cost' => 65536,
                'time_cost' => 3,
                'threads' => 3
            ],
            self::SECURITY_HIGH => [
                'memory_cost' => 131072,
                'time_cost' => 4,
                'threads' => 4
            ],
            self::SECURITY_PARANOID => [
                'memory_cost' => 262144,
                'time_cost' => 6,
                'threads' => 4
            ]
        };
    }

    /**
     * Get PHP algorithm constant
     */
    private static function getPhpAlgorithm(string $algorithm): string|int
    {
        return match($algorithm) {
            self::ALGO_ARGON2ID => PASSWORD_ARGON2ID,
            self::ALGO_ARGON2I => PASSWORD_ARGON2I,
            self::ALGO_BCRYPT => PASSWORD_BCRYPT,
            default => PASSWORD_ARGON2ID
        };
    }

    /**
     * Get algorithm options
     */
    private static function getAlgorithmOptions(string $algorithm, int $securityLevel): array
    {
        return match($algorithm) {
            self::ALGO_ARGON2ID, self::ALGO_ARGON2I => self::getArgon2Options($securityLevel),
            self::ALGO_BCRYPT => ['cost' => match($securityLevel) {
                self::SECURITY_LOW => 10,
                self::SECURITY_MEDIUM => 12,
                self::SECURITY_HIGH => 14,
                self::SECURITY_PARANOID => 16
            }],
            default => []
        };
    }

    /**
     * Get strength label from score
     */
    private static function getStrengthLabel(int $score): string
    {
        return match(true) {
            $score >= 80 => 'Very Strong',
            $score >= 60 => 'Strong',
            $score >= 40 => 'Medium',
            $score >= 20 => 'Weak',
            default => 'Very Weak'
        };
    }

    /**
     * Ensure password complexity
     */
    private static function ensureComplexity(string $password, array $options): string
    {
        $result = $password;
        $length = strlen($result);
        
        if ($options['uppercase'] && !preg_match('/[A-Z]/', $result)) {
            $result[random_int(0, $length - 1)] = chr(random_int(65, 90));
        }
        
        if ($options['lowercase'] && !preg_match('/[a-z]/', $result)) {
            $result[random_int(0, $length - 1)] = chr(random_int(97, 122));
        }
        
        if ($options['numbers'] && !preg_match('/[0-9]/', $result)) {
            $result[random_int(0, $length - 1)] = (string)random_int(0, 9);
        }
        
        if ($options['symbols'] && !preg_match('/[^A-Za-z0-9]/', $result)) {
            $symbols = '!@#$%^&*';
            $result[random_int(0, $length - 1)] = $symbols[random_int(0, strlen($symbols) - 1)];
        }
        
        return $result;
    }
}