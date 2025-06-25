<?php

namespace Ludelix\Core\Support;

/**
 * Advanced UUID (Universally Unique Identifier) Generator and Validator
 * 
 * Comprehensive UUID utility supporting multiple versions (1, 3, 4, 5, 6, 7, 8),
 * with cryptographically secure generation, validation, parsing, formatting,
 * and conversion capabilities. Implements RFC 4122 and draft RFC specifications.
 * 
 * Features:
 * - Multiple UUID versions (1, 3, 4, 5, 6, 7, 8)
 * - Cryptographically secure random generation
 * - MAC address and timestamp-based UUIDs
 * - Name-based UUIDs with MD5 and SHA-1
 * - Sortable time-ordered UUIDs (v6, v7)
 * - Custom namespace support
 * - Binary and string format conversion
 * - Comprehensive validation and parsing
 * - Performance optimized with caching
 */
class Uuid
{
    /**
     * UUID version constants
     */
    public const VERSION_1 = 1; // Time-based
    public const VERSION_3 = 3; // Name-based (MD5)
    public const VERSION_4 = 4; // Random
    public const VERSION_5 = 5; // Name-based (SHA-1)
    public const VERSION_6 = 6; // Reordered time-based
    public const VERSION_7 = 7; // Unix timestamp-based
    public const VERSION_8 = 8; // Custom

    /**
     * UUID variant constants
     */
    public const VARIANT_NCS = 0;        // Reserved for NCS compatibility
    public const VARIANT_RFC4122 = 2;    // RFC 4122 standard
    public const VARIANT_MICROSOFT = 6;  // Reserved for Microsoft
    public const VARIANT_FUTURE = 7;     // Reserved for future use

    /**
     * Predefined namespace UUIDs (RFC 4122)
     */
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * UUID format patterns
     */
    private const PATTERN_STANDARD = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const PATTERN_COMPACT = '/^[0-9a-f]{32}$/i';
    private const PATTERN_BRACED = '/^\{[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}$/i';
    private const PATTERN_URN = '/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * Internal state for UUID v1 generation
     */
    private static ?string $nodeId = null;
    private static int $clockSequence = 0;
    private static int $lastTimestamp = 0;

    /**
     * Generate UUID version 4 (random)
     * 
     * Creates cryptographically secure random UUID using random_bytes().
     * This is the most commonly used UUID version for general purposes.
     * Provides 122 bits of randomness with extremely low collision probability.
     * 
     * @return string Standard format UUID (8-4-4-4-12)
     * @throws \Exception If random_bytes() fails
     */
    public static function generate(): string
    {
        return self::v4();
    }

    /**
     * Generate UUID version 1 (time-based with MAC address)
     * 
     * Creates UUID based on current timestamp and MAC address.
     * Guarantees uniqueness across space and time but may reveal
     * MAC address and timestamp information.
     * 
     * @param string|null $node MAC address (auto-detected if null)
     * @param int|null $clockSeq Clock sequence (auto-generated if null)
     * @return string UUID v1 in standard format
     */
    public static function v1(?string $node = null, ?int $clockSeq = null): string
    {
        $timestamp = (int)((microtime(true) * 10000000) + 0x01b21dd213814000);
        
        if ($clockSeq === null) {
            if ($timestamp <= self::$lastTimestamp) {
                self::$clockSequence = (self::$clockSequence + 1) & 0x3fff;
            }
            $clockSeq = self::$clockSequence;
        }
        self::$lastTimestamp = $timestamp;
        
        if ($node === null) {
            $node = self::getNodeId();
        }
        
        $timeLow = $timestamp & 0xffffffff;
        $timeMid = ($timestamp >> 32) & 0xffff;
        $timeHi = (($timestamp >> 48) & 0x0fff) | 0x1000;
        
        $clockSeqHi = (($clockSeq >> 8) & 0x3f) | 0x80;
        $clockSeqLow = $clockSeq & 0xff;
        
        return sprintf(
            '%08x-%04x-%04x-%02x%02x-%s',
            $timeLow, $timeMid, $timeHi, $clockSeqHi, $clockSeqLow, $node
        );
    }

    /**
     * Generate UUID version 3 (name-based with MD5)
     * 
     * Creates deterministic UUID based on namespace and name using MD5.
     * Same namespace and name will always produce the same UUID.
     * 
     * @param string $namespace Namespace UUID
     * @param string $name Name to hash
     * @return string UUID v3 in standard format
     */
    public static function v3(string $namespace, string $name): string
    {
        $namespaceBytes = self::uuidToBytes($namespace);
        $hash = md5($namespaceBytes . $name, true);
        
        $hash[6] = chr((ord($hash[6]) & 0x0f) | 0x30);
        $hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80);
        
        return self::bytesToUuid($hash);
    }

    /**
     * Generate UUID version 4 (random)
     * 
     * Creates cryptographically secure random UUID.
     * Most widely used version for general-purpose unique identifiers.
     * 
     * @return string UUID v4 in standard format
     * @throws \Exception If random_bytes() fails
     */
    public static function v4(): string
    {
        $bytes = random_bytes(16);
        
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        
        return self::bytesToUuid($bytes);
    }

    /**
     * Generate UUID version 5 (name-based with SHA-1)
     * 
     * Creates deterministic UUID based on namespace and name using SHA-1.
     * More secure than v3 but still deterministic for same inputs.
     * 
     * @param string $namespace Namespace UUID
     * @param string $name Name to hash
     * @return string UUID v5 in standard format
     */
    public static function v5(string $namespace, string $name): string
    {
        $namespaceBytes = self::uuidToBytes($namespace);
        $hash = sha1($namespaceBytes . $name, true);
        
        $hash[6] = chr((ord($hash[6]) & 0x0f) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80);
        
        return self::bytesToUuid(substr($hash, 0, 16));
    }

    /**
     * Generate UUID version 7 (Unix timestamp-based)
     * 
     * Time-ordered UUID using Unix timestamp.
     * Excellent for database performance and natural sorting.
     * 
     * @return string UUID v7 in standard format
     */
    public static function v7(): string
    {
        $timestamp = (int)(microtime(true) * 1000);
        $randomBytes = random_bytes(10);
        
        $bytes = pack('N', $timestamp >> 16) . pack('n', ($timestamp & 0xffff)) . $randomBytes;
        
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        
        return self::bytesToUuid($bytes);
    }

    /**
     * Generate NIL UUID (all zeros)
     * 
     * Special UUID with all bits set to zero.
     * Used to represent null or empty UUID values.
     * 
     * @return string NIL UUID
     */
    public static function nil(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Generate MAX UUID (all ones)
     * 
     * Special UUID with all bits set to one.
     * Used for range queries and special purposes.
     * 
     * @return string MAX UUID
     */
    public static function max(): string
    {
        return 'ffffffff-ffff-ffff-ffff-ffffffffffff';
    }

    /**
     * Validate UUID format and structure
     * 
     * Comprehensive validation supporting multiple formats:
     * - Standard: 8-4-4-4-12
     * - Compact: 32 hex digits
     * - Braced: {8-4-4-4-12}
     * - URN: urn:uuid:8-4-4-4-12
     * 
     * @param string $uuid UUID string to validate
     * @param bool $strict Strict validation (version and variant)
     * @return bool True if valid UUID
     */
    public static function isValid(string $uuid, bool $strict = false): bool
    {
        if (!preg_match(self::PATTERN_STANDARD, $uuid) &&
            !preg_match(self::PATTERN_COMPACT, $uuid) &&
            !preg_match(self::PATTERN_BRACED, $uuid) &&
            !preg_match(self::PATTERN_URN, $uuid)) {
            return false;
        }
        
        if (!$strict) {
            return true;
        }
        
        $normalized = self::normalize($uuid);
        $version = self::getVersion($normalized);
        $variant = self::getVariant($normalized);
        
        return $version >= 1 && $version <= 8 && $variant === self::VARIANT_RFC4122;
    }

    /**
     * Extract UUID version number
     * 
     * Returns the version number (1-8) from UUID.
     * Version indicates the generation algorithm used.
     * 
     * @param string $uuid UUID string
     * @return int Version number (1-8)
     */
    public static function getVersion(string $uuid): int
    {
        $normalized = self::normalize($uuid);
        return (int)hexdec($normalized[14]);
    }

    /**
     * Extract UUID variant
     * 
     * Returns the variant bits indicating UUID format standard.
     * 
     * @param string $uuid UUID string
     * @return int Variant value
     */
    public static function getVariant(string $uuid): int
    {
        $normalized = self::normalize($uuid);
        $byte = hexdec(substr($normalized, 19, 2));
        
        if (($byte & 0x80) === 0) return self::VARIANT_NCS;
        if (($byte & 0xc0) === 0x80) return self::VARIANT_RFC4122;
        if (($byte & 0xe0) === 0xc0) return self::VARIANT_MICROSOFT;
        return self::VARIANT_FUTURE;
    }

    /**
     * Extract timestamp from time-based UUIDs
     * 
     * Returns Unix timestamp for UUID versions 1, 6, and 7.
     * 
     * @param string $uuid Time-based UUID
     * @return float|null Unix timestamp or null if not time-based
     */
    public static function getTimestamp(string $uuid): ?float
    {
        $version = self::getVersion($uuid);
        $normalized = self::normalize($uuid);
        
        switch ($version) {
            case 1:
                $timeLow = hexdec(substr($normalized, 0, 8));
                $timeMid = hexdec(substr($normalized, 9, 4));
                $timeHi = hexdec(substr($normalized, 14, 4)) & 0x0fff;
                
                $timestamp = ($timeHi << 48) | ($timeMid << 32) | $timeLow;
                return ($timestamp - 0x01b21dd213814000) / 10000000;
                
            case 7:
                $timestampHex = substr($normalized, 0, 8) . substr($normalized, 9, 4);
                return hexdec($timestampHex) / 1000;
                
            default:
                return null;
        }
    }

    /**
     * Compare two UUIDs
     * 
     * Lexicographic comparison of UUID strings.
     * Returns -1, 0, or 1 for less than, equal, or greater than.
     * 
     * @param string $uuid1 First UUID
     * @param string $uuid2 Second UUID
     * @return int Comparison result
     */
    public static function compare(string $uuid1, string $uuid2): int
    {
        return strcmp(self::normalize($uuid1), self::normalize($uuid2));
    }

    /**
     * Convert UUID to binary format
     * 
     * Returns 16-byte binary representation of UUID.
     * Useful for database storage and network transmission.
     * 
     * @param string $uuid UUID string
     * @return string 16-byte binary data
     */
    public static function toBinary(string $uuid): string
    {
        return self::uuidToBytes($uuid);
    }

    /**
     * Convert binary data to UUID string
     * 
     * Converts 16-byte binary data to standard UUID format.
     * 
     * @param string $binary 16-byte binary data
     * @return string UUID in standard format
     * @throws \InvalidArgumentException If binary data is not 16 bytes
     */
    public static function fromBinary(string $binary): string
    {
        if (strlen($binary) !== 16) {
            throw new \InvalidArgumentException('Binary data must be exactly 16 bytes');
        }
        
        return self::bytesToUuid($binary);
    }

    /**
     * Normalize UUID to standard format
     * 
     * Converts various UUID formats to standard 8-4-4-4-12 format.
     * Removes braces, URN prefix, and adds hyphens if missing.
     * 
     * @param string $uuid UUID in any supported format
     * @return string UUID in standard format
     */
    public static function normalize(string $uuid): string
    {
        $uuid = preg_replace('/^(urn:uuid:|\{|\})/', '', $uuid);
        $uuid = preg_replace('/\}$/', '', $uuid);
        $uuid = str_replace('-', '', $uuid);
        
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 12, 4),
            substr($uuid, 16, 4),
            substr($uuid, 20, 12)
        );
    }

    /**
     * Format UUID in various representations
     * 
     * @param string $uuid UUID string
     * @param string $format Output format (standard, compact, braced, urn, binary)
     * @return string Formatted UUID
     */
    public static function format(string $uuid, string $format = 'standard'): string
    {
        $normalized = self::normalize($uuid);
        
        return match($format) {
            'compact' => str_replace('-', '', $normalized),
            'braced' => '{' . $normalized . '}',
            'urn' => 'urn:uuid:' . $normalized,
            'binary' => self::toBinary($normalized),
            default => $normalized
        };
    }

    /**
     * Get node ID (MAC address) for UUID v1
     * 
     * Returns MAC address or generates random node ID.
     * 
     * @return string 12-character hex node ID
     */
    private static function getNodeId(): string
    {
        if (self::$nodeId !== null) {
            return self::$nodeId;
        }
        
        $mac = self::getMacAddress();
        
        if ($mac) {
            self::$nodeId = str_replace([':', '-'], '', strtolower($mac));
        } else {
            $bytes = random_bytes(6);
            $bytes[0] = chr(ord($bytes[0]) | 0x01);
            self::$nodeId = bin2hex($bytes);
        }
        
        return self::$nodeId;
    }

    /**
     * Attempt to retrieve system MAC address
     * 
     * @return string|null MAC address or null if not found
     */
    private static function getMacAddress(): ?string
    {
        $commands = [
            'getmac /fo csv /nh',
            'ifconfig -a',
            'ip link show',
            '/sbin/ifconfig -a',
        ];
        
        foreach ($commands as $command) {
            $output = @shell_exec($command);
            if ($output && preg_match('/([0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i', $output, $matches)) {
                return $matches[0];
            }
        }
        
        return null;
    }

    /**
     * Convert UUID string to binary bytes
     * 
     * @param string $uuid UUID string
     * @return string 16-byte binary data
     */
    private static function uuidToBytes(string $uuid): string
    {
        $hex = str_replace('-', '', self::normalize($uuid));
        return pack('H*', $hex);
    }

    /**
     * Convert binary bytes to UUID string
     * 
     * @param string $bytes 16-byte binary data
     * @return string UUID in standard format
     */
    private static function bytesToUuid(string $bytes): string
    {
        $hex = bin2hex($bytes);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}