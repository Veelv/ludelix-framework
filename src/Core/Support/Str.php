<?php

namespace Ludelix\Core\Support;

/**
 * Advanced String Manipulation Utility
 * 
 * Comprehensive string processing toolkit with Unicode support,
 * advanced formatting, validation, and transformation capabilities.
 * Optimized for performance and internationalization.
 */
class Str
{
    /**
     * Character mapping for transliteration
     * Maps accented characters to their ASCII equivalents
     */
    private static array $transliterationMap = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
        'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
        'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
        'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Þ' => 'TH', 'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
        'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
        'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
        'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y'
    ];

    /**
     * Generate URL-friendly slug from text with advanced options
     * 
     * Converts text to lowercase, removes special characters, handles Unicode,
     * applies transliteration, and ensures proper formatting for URLs.
     * Supports custom separators, length limits, and collision handling.
     * 
     * @param string $text Input text to convert
     * @param string $separator Character to separate words (default: '-')
     * @param int|null $maxLength Maximum length of generated slug
     * @param bool $preserveCase Whether to preserve original case
     * @param bool $strict Use strict mode (ASCII only)
     * @return string Generated slug
     */
    public function slug(string $text, string $separator = '-', ?int $maxLength = null, bool $preserveCase = false, bool $strict = false): string
    {
        // Handle empty input
        if (empty(trim($text))) {
            return '';
        }

        // Apply transliteration for better ASCII conversion
        $text = $this->transliterate($text);
        
        // Convert to lowercase unless preserving case
        if (!$preserveCase) {
            $text = mb_strtolower($text, 'UTF-8');
        }
        
        // Remove HTML tags and decode entities
        $text = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        
        if ($strict) {
            // Strict mode: ASCII only
            $text = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $text);
        } else {
            // Unicode mode: preserve Unicode letters and numbers
            $text = preg_replace('/[^\p{L}\p{Nd}\s\-_]/u', '', $text);
        }
        
        // Replace multiple whitespace/separators with single separator
        $text = preg_replace('/[\s\-_]+/', $separator, $text);
        
        // Trim separators from ends
        $text = trim($text, $separator);
        
        // Apply length limit if specified
        if ($maxLength && mb_strlen($text, 'UTF-8') > $maxLength) {
            $text = mb_substr($text, 0, $maxLength, 'UTF-8');
            $text = rtrim($text, $separator);
        }
        
        return $text;
    }

    /**
     * Convert string to camelCase format
     * 
     * Transforms text to camelCase by capitalizing first letter of each word
     * except the first, and removing separators. Handles Unicode properly.
     * 
     * @param string $text Input text
     * @param string $delimiter Characters to treat as word separators
     * @return string camelCase formatted string
     */
    public function camel(string $text, string $delimiter = ' -_'): string
    {
        return lcfirst($this->studly($text, $delimiter));
    }

    /**
     * Convert string to StudlyCase (PascalCase) format
     * 
     * Capitalizes first letter of each word and removes separators.
     * Supports Unicode characters and custom delimiters.
     * 
     * @param string $text Input text
     * @param string $delimiter Characters to treat as word separators
     * @return string StudlyCase formatted string
     */
    public function studly(string $text, string $delimiter = ' -_'): string
    {
        $text = mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
        return preg_replace('/[' . preg_quote($delimiter, '/') . ']+/', '', $text);
    }

    /**
     * Convert string to snake_case format
     * 
     * Transforms camelCase or StudlyCase to snake_case by inserting
     * delimiter before uppercase letters and converting to lowercase.
     * 
     * @param string $text Input text
     * @param string $delimiter Delimiter to use (default: '_')
     * @return string snake_case formatted string
     */
    public function snake(string $text, string $delimiter = '_'): string
    {
        $text = preg_replace('/([a-z])([A-Z])/', '$1' . $delimiter . '$2', $text);
        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Convert string to kebab-case format
     * 
     * Similar to snake_case but uses hyphens instead of underscores.
     * Commonly used for CSS classes and URL segments.
     * 
     * @param string $text Input text
     * @return string kebab-case formatted string
     */
    public function kebab(string $text): string
    {
        return $this->snake($text, '-');
    }

    /**
     * Convert string to Title Case format
     * 
     * Capitalizes first letter of each word while preserving
     * word boundaries and handling Unicode properly.
     * 
     * @param string $text Input text
     * @return string Title Case formatted string
     */
    public function title(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate cryptographically secure random string
     * 
     * Creates random string using specified character set.
     * Suitable for tokens, passwords, and unique identifiers.
     * 
     * @param int $length Desired length of random string
     * @param string $charset Character set to use
     * @return string Generated random string
     * @throws \Exception If random_bytes fails
     */
    public function random(int $length = 16, string $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        if ($length <= 0) {
            return '';
        }
        
        $charsetLength = strlen($charset);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $charset[random_int(0, $charsetLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Generate random hexadecimal string
     * 
     * Creates random hex string of specified length.
     * Useful for generating tokens and identifiers.
     * 
     * @param int $length Desired length (must be even)
     * @return string Random hex string
     */
    public function randomHex(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Check if string contains substring (case-sensitive)
     * 
     * Unicode-aware substring search with optional case sensitivity.
     * 
     * @param string $haystack String to search in
     * @param string $needle String to search for
     * @param bool $caseSensitive Whether search is case-sensitive
     * @return bool True if needle found in haystack
     */
    public function contains(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return str_contains($haystack, $needle);
        }
        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    /**
     * Check if string starts with given prefix
     * 
     * Unicode-aware prefix checking with case sensitivity option.
     * 
     * @param string $haystack String to check
     * @param string $needle Prefix to look for
     * @param bool $caseSensitive Whether check is case-sensitive
     * @return bool True if haystack starts with needle
     */
    public function startsWith(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return str_starts_with($haystack, $needle);
        }
        return mb_stripos($haystack, $needle, 0, 'UTF-8') === 0;
    }

    /**
     * Check if string ends with given suffix
     * 
     * Unicode-aware suffix checking with case sensitivity option.
     * 
     * @param string $haystack String to check
     * @param string $needle Suffix to look for
     * @param bool $caseSensitive Whether check is case-sensitive
     * @return bool True if haystack ends with needle
     */
    public function endsWith(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return str_ends_with($haystack, $needle);
        }
        $length = mb_strlen($needle, 'UTF-8');
        return mb_substr($haystack, -$length, null, 'UTF-8') === $needle;
    }

    /**
     * Limit string length with ellipsis
     * 
     * Truncates string to specified length and adds ellipsis.
     * Preserves word boundaries when possible.
     * 
     * @param string $text Input text
     * @param int $limit Maximum length
     * @param string $end String to append when truncated
     * @param bool $preserveWords Whether to preserve word boundaries
     * @return string Truncated string
     */
    public function limit(string $text, int $limit = 100, string $end = '...', bool $preserveWords = true): string
    {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $limit - mb_strlen($end, 'UTF-8'), 'UTF-8');
        
        if ($preserveWords && preg_match('/\s/', $truncated)) {
            $truncated = preg_replace('/\s+[^\s]*$/', '', $truncated);
        }
        
        return $truncated . $end;
    }

    /**
     * Transliterate Unicode characters to ASCII
     * 
     * Converts accented and special characters to their ASCII equivalents.
     * Useful for creating ASCII-safe slugs and identifiers.
     * 
     * @param string $text Input text with Unicode characters
     * @return string ASCII transliterated text
     */
    public function transliterate(string $text): string
    {
        return strtr($text, self::$transliterationMap);
    }

    /**
     * Remove all whitespace from string
     * 
     * Removes all types of whitespace characters including
     * spaces, tabs, newlines, and Unicode whitespace.
     * 
     * @param string $text Input text
     * @return string Text with whitespace removed
     */
    public function removeWhitespace(string $text): string
    {
        return preg_replace('/\s+/u', '', $text);
    }

    /**
     * Normalize whitespace in string
     * 
     * Converts multiple consecutive whitespace characters
     * to single spaces and trims the result.
     * 
     * @param string $text Input text
     * @return string Normalized text
     */
    public function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Mask string with specified character
     * 
     * Replaces part of string with mask character for privacy.
     * Useful for masking emails, phone numbers, etc.
     * 
     * @param string $text Input text to mask
     * @param int $start Starting position (negative for end-relative)
     * @param int|null $length Length to mask (null for rest of string)
     * @param string $mask Character to use for masking
     * @return string Masked string
     */
    public function mask(string $text, int $start = 0, ?int $length = null, string $mask = '*'): string
    {
        $textLength = mb_strlen($text, 'UTF-8');
        
        if ($start < 0) {
            $start = max(0, $textLength + $start);
        }
        
        if ($length === null) {
            $length = $textLength - $start;
        }
        
        $masked = str_repeat($mask, $length);
        
        return mb_substr($text, 0, $start, 'UTF-8') . $masked . mb_substr($text, $start + $length, null, 'UTF-8');
    }

    /**
     * Reverse string with Unicode support
     * 
     * Reverses string while properly handling Unicode characters.
     * 
     * @param string $text Input text
     * @return string Reversed text
     */
    public function reverse(string $text): string
    {
        return implode('', array_reverse(mb_str_split($text, 1, 'UTF-8')));
    }

    /**
     * Count words in string
     * 
     * Counts words using Unicode-aware word boundaries.
     * Handles multiple languages and character sets.
     * 
     * @param string $text Input text
     * @return int Number of words
     */
    public function wordCount(string $text): int
    {
        return str_word_count(strip_tags($text), 0, 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
    }

    /**
     * Extract words from string
     * 
     * Returns array of words found in string.
     * 
     * @param string $text Input text
     * @return array Array of words
     */
    public function words(string $text): array
    {
        return str_word_count(strip_tags($text), 1, 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
    }

    /**
     * Pluralize English word
     * 
     * Simple English pluralization with common rules.
     * For complex pluralization, use dedicated libraries.
     * 
     * @param string $word Singular word
     * @param int $count Count to determine plural form
     * @return string Pluralized word
     */
    public function plural(string $word, int $count = 2): string
    {
        if ($count === 1) {
            return $word;
        }
        
        // Simple pluralization rules
        if (preg_match('/(s|sh|ch|x|z)$/i', $word)) {
            return $word . 'es';
        }
        
        if (preg_match('/[^aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }
        
        if (preg_match('/f$/i', $word)) {
            return substr($word, 0, -1) . 'ves';
        }
        
        if (preg_match('/fe$/i', $word)) {
            return substr($word, 0, -2) . 'ves';
        }
        
        return $word . 's';
    }

    /**
     * Generate string hash
     * 
     * Creates hash of string using specified algorithm.
     * 
     * @param string $text Input text
     * @param string $algorithm Hash algorithm
     * @return string Generated hash
     */
    public function hash(string $text, string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $text);
    }

    /**
     * Pad string to specified length
     * 
     * Pads string with specified character to reach target length.
     * Supports left, right, and both padding modes.
     * 
     * @param string $text Input text
     * @param int $length Target length
     * @param string $pad Padding character
     * @param string $type Padding type (left, right, both)
     * @return string Padded string
     */
    public function pad(string $text, int $length, string $pad = ' ', string $type = 'right'): string
    {
        $textLength = mb_strlen($text, 'UTF-8');
        
        if ($textLength >= $length) {
            return $text;
        }
        
        $padLength = $length - $textLength;
        
        return match($type) {
            'left' => str_repeat($pad, $padLength) . $text,
            'both' => str_repeat($pad, intval($padLength / 2)) . $text . str_repeat($pad, intval(ceil($padLength / 2))),
            default => $text . str_repeat($pad, $padLength)
        };
    }

    /**
     * Extract substring between two delimiters
     * 
     * Returns text between first occurrence of start and end delimiters.
     * 
     * @param string $text Input text
     * @param string $start Start delimiter
     * @param string $end End delimiter
     * @param bool $inclusive Whether to include delimiters
     * @return string|null Extracted substring or null if not found
     */
    public function between(string $text, string $start, string $end, bool $inclusive = false): ?string
    {
        $startPos = mb_strpos($text, $start, 0, 'UTF-8');
        if ($startPos === false) {
            return null;
        }
        
        $startPos += $inclusive ? 0 : mb_strlen($start, 'UTF-8');
        $endPos = mb_strpos($text, $end, $startPos, 'UTF-8');
        
        if ($endPos === false) {
            return null;
        }
        
        if ($inclusive) {
            $endPos += mb_strlen($end, 'UTF-8');
        }
        
        return mb_substr($text, $startPos, $endPos - $startPos, 'UTF-8');
    }

    /**
     * Extract substring before first occurrence of delimiter
     * 
     * @param string $text Input text
     * @param string $delimiter Delimiter to search for
     * @return string Substring before delimiter
     */
    public function before(string $text, string $delimiter): string
    {
        $pos = mb_strpos($text, $delimiter, 0, 'UTF-8');
        return $pos === false ? $text : mb_substr($text, 0, $pos, 'UTF-8');
    }

    /**
     * Extract substring after first occurrence of delimiter
     * 
     * @param string $text Input text
     * @param string $delimiter Delimiter to search for
     * @return string Substring after delimiter
     */
    public function after(string $text, string $delimiter): string
    {
        $pos = mb_strpos($text, $delimiter, 0, 'UTF-8');
        return $pos === false ? '' : mb_substr($text, $pos + mb_strlen($delimiter, 'UTF-8'), null, 'UTF-8');
    }

    /**
     * Replace first occurrence of substring
     * 
     * @param string $text Input text
     * @param string $search String to search for
     * @param string $replace Replacement string
     * @return string Text with first occurrence replaced
     */
    public function replaceFirst(string $text, string $search, string $replace): string
    {
        $pos = mb_strpos($text, $search, 0, 'UTF-8');
        if ($pos === false) {
            return $text;
        }
        
        return mb_substr($text, 0, $pos, 'UTF-8') . $replace . mb_substr($text, $pos + mb_strlen($search, 'UTF-8'), null, 'UTF-8');
    }

    /**
     * Check if string is valid email format
     * 
     * @param string $email Email string to validate
     * @return bool True if valid email format
     */
    public function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if string is valid URL format
     * 
     * @param string $url URL string to validate
     * @return bool True if valid URL format
     */
    public function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if string is valid JSON
     * 
     * @param string $text Input text
     * @return bool True if valid JSON
     */
    public function isJson(string $text): bool
    {
        json_decode($text);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if string contains only alphabetic characters
     * 
     * @param string $text Input text
     * @return bool True if only alphabetic
     */
    public function isAlpha(string $text): bool
    {
        return ctype_alpha($text);
    }

    /**
     * Check if string contains only alphanumeric characters
     * 
     * @param string $text Input text
     * @return bool True if only alphanumeric
     */
    public function isAlphaNumeric(string $text): bool
    {
        return ctype_alnum($text);
    }

    /**
     * Generate Lorem Ipsum text
     * 
     * Creates placeholder text for testing and design purposes.
     * 
     * @param int $words Number of words to generate
     * @param bool $startWithLorem Whether to start with "Lorem ipsum"
     * @return string Generated Lorem Ipsum text
     */
    public function lorem(int $words = 50, bool $startWithLorem = true): string
    {
        $loremWords = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
            'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
            'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
            'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo'
        ];
        
        $result = [];
        
        if ($startWithLorem && $words >= 2) {
            $result[] = 'Lorem';
            $result[] = 'ipsum';
            $words -= 2;
        }
        
        for ($i = 0; $i < $words; $i++) {
            $result[] = $loremWords[array_rand($loremWords)];
        }
        
        return implode(' ', $result) . '.';
    }

    /**
     * Calculate similarity percentage between two strings
     * 
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity percentage (0-100)
     */
    public function similarity(string $str1, string $str2): float
    {
        similar_text($str1, $str2, $percent);
        return $percent;
    }

    /**
     * Convert string to binary representation
     * 
     * @param string $text Input text
     * @return string Binary representation
     */
    public function toBinary(string $text): string
    {
        $result = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $result .= sprintf('%08b', ord($text[$i])) . ' ';
        }
        return rtrim($result);
    }

    /**
     * Wrap text to specified line length
     * 
     * Breaks text into lines of specified maximum length.
     * Preserves word boundaries and handles Unicode properly.
     * 
     * @param string $text Input text
     * @param int $width Maximum line width
     * @param string $break Line break character
     * @param bool $cut Whether to cut long words
     * @return string Wrapped text
     */
    public function wrap(string $text, int $width = 75, string $break = "\n", bool $cut = false): string
    {
        return wordwrap($text, $width, $break, $cut);
    }

    /**
     * Convert string to alternating case
     * 
     * @param string $text Input text
     * @return string Text in alternating case
     */
    public function alternatingCase(string $text): string
    {
        $result = '';
        $upper = true;
        
        for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            if (ctype_alpha($char)) {
                $result .= $upper ? mb_strtoupper($char, 'UTF-8') : mb_strtolower($char, 'UTF-8');
                $upper = !$upper;
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    /**
     * Calculate Levenshtein distance between two strings
     * 
     * Measures the minimum number of single-character edits
     * required to change one string into another.
     * 
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return int Edit distance
     */
    public function levenshtein(string $str1, string $str2): int
    {
        return levenshtein($str1, $str2);
    }
}