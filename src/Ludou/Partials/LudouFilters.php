<?php

namespace Ludelix\Ludou\Partials;

use Ludelix\Core\Support\Str;

class LudouFilters
{
    private static $filters = [];
    private static ?Str $strInstance = null;

    public static function getDefaultFilters(): array
    {
        return [
            'upper' => [self::class, 'upper'],
            'lower' => [self::class, 'lower'],
            'capitalize' => [self::class, 'capitalize'],
            'escape' => [self::class, 'escape'],
            'trim' => [self::class, 'trim'],
            'ucfirst' => [self::class, 'ucfirst'],
            'ucwords' => [self::class, 'ucwords'],
            'number' => [self::class, 'number'],
            'abs' => [self::class, 'abs'],
            'round' => [self::class, 'round'],
            'length' => [self::class, 'length'],
            'join' => [self::class, 'join'],
            'split' => [self::class, 'split'],
            'first' => [self::class, 'first'],
            'last' => [self::class, 'last'],
            'date' => [self::class, 'date'],
            'nl2br' => [self::class, 'nl2br'],
            'json' => [self::class, 'json'],
            'limit' => [self::class, 'limit'],
            'truncate' => [self::class, 'truncate'],
            'stripTags' => [self::class, 'stripTags'],
            'replace' => [self::class, 'replace'],
            'reverse' => [self::class, 'reverse'],
            'shuffle' => [self::class, 'shuffle'],
            'default' => [self::class, 'default'],
            'asset' => [self::class, 'asset'],
            'slug' => [self::class, 'slug'],
            'camel' => [self::class, 'camel'],
            'snake' => [self::class, 'snake'],
            'currency' => [self::class, 'currency'],
        ];
    }

    public static function apply(string $filterName, ...$args)
    {
        if (empty($args)) {
            return '';
        }
        if (isset(self::$filters[$filterName])) {
            return call_user_func(self::$filters[$filterName], ...$args);
        }
        $defaultFilters = self::getDefaultFilters();
        if (isset($defaultFilters[$filterName])) {
            return call_user_func($defaultFilters[$filterName], ...$args);
        }
        if (class_exists('Ludelix\\Ludou\\Partials\\LudouExtensions') && LudouExtensions::hasFilter($filterName)) {
            return LudouExtensions::applyFilter($filterName, ...$args);
        }
        throw new \InvalidArgumentException("Filtro '{$filterName}' não existe. Se você queria exibir uma variável, use #[\${$filterName}] no template.");
    }

    public static function addFilter(string $name, callable $callback)
    {
        self::$filters[$name] = $callback;
    }

    /**
     * Converts a string to uppercase.
     * Usage: #[$myString | upper]
     * @param mixed $value
     * @return string
     */
    public static function upper($value): string
    {
        return mb_strtoupper((string) $value);
    }

    /**
     * Converts a string to lowercase.
     * Usage: #[$myString | lower]
     * @param mixed $value
     * @return string
     */
    public static function lower($value): string
    {
        return mb_strtolower((string) $value);
    }

    /**
     * Converts a string to title case.
     * Usage: #[$myString | capitalize]
     * @param mixed $value
     * @return string
     */
    public static function capitalize($value): string
    {
        return mb_convert_case((string) $value, MB_CASE_TITLE);
    }

    /**
     * Escapes a string for HTML output.
     * Usage: #[$htmlContent | escape]
     * @param mixed $value
     * @return string
     */
    public static function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Strips whitespace from the beginning and end of a string.
     * Usage: #[$myString | trim]
     * @param mixed $value
     * @return string
     */
    public static function trim($value): string
    {
        return trim((string) $value);
    }

    /**
     * Makes a string's first character uppercase.
     * Usage: #[$myString | ucfirst]
     * @param mixed $value
     * @return string
     */
    public static function ucfirst($value): string
    {
        return ucfirst((string) $value);
    }

    /**
     * Uppercases the first character of each word in a string.
     * Usage: #[$myString | ucwords]
     * @param mixed $value
     * @return string
     */
    public static function ucwords($value): string
    {
        return ucwords((string) $value);
    }

    /**
     * Formats a number with grouped thousands.
     * Usage: #[$myNumber | number(2, ',', '.')]
     * @param mixed $value
     * @param int $decimals
     * @param string $decPoint
     * @param string $thousandsSep
     * @return string
     */
    public static function number($value, int $decimals = 0, string $decPoint = '.', string $thousandsSep = ','): string
    {
        return number_format($value, $decimals, $decPoint, $thousandsSep);
    }

    /**
     * Returns the absolute value of a number.
     * Usage: #[-10 | abs]
     * @param mixed $value
     * @return float
     */
    public static function abs($value): float
    {
        return abs($value);
    }

    /**
     * Rounds a number.
     * Usage: #[1.234 | round(2)]
     * @param mixed $value
     * @param int $precision
     * @return float
     */
    public static function round($value, int $precision = 0): float
    {
        return round($value, $precision);
    }

    /**
     * Returns the length of a string or array.
     * Usage: #[$myVar | length]
     * @param mixed $value
     * @return int
     */
    public static function length($value): int
    {
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }
        return mb_strlen((string) $value);
    }

    /**
     * Joins array elements with a string.
     * Usage: #[$myArray | join(', ')]
     * @param mixed $value
     * @param string $glue
     * @return string
     */
    public static function join($value, string $glue = ''): string
    {
        return implode($glue, (array) $value);
    }

    /**
     * Splits a string by a string.
     * Usage: #["a,b,c" | split(',')]
     * @param mixed $value
     * @param string $delimiter
     * @return array
     */
    public static function split($value, string $delimiter = ''): array
    {
        return explode($delimiter, (string) $value);
    }

    /**
     * Gets the first element of an array or the first character of a string.
     * Usage: #[$myVar | first]
     * @param mixed $value
     * @return mixed
     */
    public static function first($value)
    {
        if (is_array($value)) {
            return reset($value);
        }
        return mb_substr((string) $value, 0, 1);
    }

    /**
     * Gets the last element of an array or the last character of a string.
     * Usage: #[$myVar | last]
     * @param mixed $value
     * @return mixed
     */
    public static function last($value)
    {
        if (is_array($value)) {
            return end($value);
        }
        return mb_substr((string) $value, -1);
    }

    /**
     * Formats a date.
     * Usage: #[$myDate | date('d/m/Y')]
     * @param mixed $value
     * @param string $format
     * @return string
     */
    public static function date($value, string $format = 'Y-m-d H:i:s'): string
    {
        if ($value instanceof \DateTime) {
            return $value->format($format);
        }
        return date($format, is_numeric($value) ? $value : strtotime($value));
    }

    /**
     * Inserts HTML line breaks before all newlines in a string.
     * Usage: #[$myText | nl2br]
     * @param mixed $value
     * @return string
     */
    public static function nl2br($value): string
    {
        return nl2br((string) $value);
    }

    /**
     * Converts a value to its JSON representation.
     * Usage: #[$myArray | json]
     * @param mixed $value
     * @return string
     */
    public static function json($value): string
    {
        // Check if the value is a string that represents an array
        if (is_string($value) && preg_match('/^\[.*\]$/', trim($value))) {
            // Try to convert the string into an array
            $arrayValue = eval ("return $value;"); // Warning: eval can be dangerous if not controlled
            if (is_array($arrayValue)) {
                return json_encode($arrayValue, JSON_UNESCAPED_UNICODE);
            }
        }
        // Check if the value is an array
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        // If it's not an array, just return the value as JSON
        return json_encode((string) $value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Truncates a string to a given length.
     * Usage: #[$myText | truncate(50, '...')]
     * @param mixed $value
     * @param mixed ...$args
     * @return string
     */
    public static function truncate($value, ...$args): string
    {
        $opt = self::extractNamedArgs($args, ['length', 'suffix'], ['length' => 30, 'suffix' => '...']);
        $value = (string) $value;
        if (mb_strlen($value) <= $opt['length']) {
            return $value;
        }
        return mb_substr($value, 0, $opt['length']) . $opt['suffix'];
    }

    /**
     * Strips HTML and PHP tags from a string.
     * Usage: #[$htmlContent | stripTags]
     * @param mixed $value
     * @return string
     */
    public static function stripTags($value): string
    {
        return strip_tags((string) $value);
    }

    /**
     * Replaces text in a string.
     * Usage: #[$myString | replace('search', 'replace')]
     * @param mixed $value
     * @param mixed ...$args
     * @return string
     */
    public static function replace($value, ...$args): string
    {
        $opt = self::extractNamedArgs($args, ['search', 'replace'], ['search' => '', 'replace' => '']);
        return str_replace($opt['search'], $opt['replace'], (string) $value);
    }

    /**
     * Reverses an array or a string.
     * Usage: #[$myVar | reverse]
     * @param mixed $value
     * @return mixed
     */
    public static function reverse($value)
    {
        if (is_array($value)) {
            return array_reverse($value);
        }
        return strrev((string) $value);
    }

    /**
     * Shuffles an array or a string.
     * Usage: #[$myVar | shuffle]
     * @param mixed $value
     * @return mixed
     */
    public static function shuffle($value)
    {
        if (is_array($value)) {
            shuffle($value);
            return $value;
        }
        return str_shuffle((string) $value);
    }

    /**
     * Returns a default value if the variable is empty.
     * Usage: #[$myVar | default('fallback')]
     * @param mixed $value
     * @param string $default
     * @return mixed
     */
    public static function default($value, $default = '')
    {
        return empty($value) ? $default : $value;
    }

    /**
     * Generates a URL for an asset.
     * Usage: #['css/app.css' | asset]
     * @param mixed $path
     * @param mixed ...$args
     * @return string
     */
    public static function asset($path, ...$args)
    {
        // Join all arguments into a single path, in case the parser splits them
        $allParts = is_array($path) ? $path : [$path];
        foreach ($args as $a) {
            if (is_array($a)) {
                $allParts = array_merge($allParts, $a);
            } else {
                $allParts[] = $a;
            }
        }
        $cleanPath = implode('/', array_map(function ($a) {
            return trim(str_replace('\\', '/', $a), '/');
        }, $allParts));
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        return '/' . $cleanPath;
    }

    private static function isValidFilter($filterName)
    {
        $validFilters = array_keys(self::getDefaultFilters());
        return in_array($filterName, $validFilters);
    }

    private static function extractNamedArgs(array &$args, array $names, $defaults = [])
    {
        $named = $defaults;
        if (!empty($args) && is_array(end($args)) && self::isAssoc(end($args))) {
            $assoc = array_pop($args);
            foreach ($names as $n) {
                if (isset($assoc[$n]))
                    $named[$n] = $assoc[$n];
            }
        }
        foreach ($names as $i => $n) {
            if (isset($args[$i]))
                $named[$n] = $args[$i];
        }
        return $named;
    }

    private static function isAssoc(array $arr)
    {
        foreach (array_keys($arr) as $k)
            if (!is_int($k))
                return true;
        return false;
    }

    /**
     * Garante uma única instância da classe Str.
     */
    private static function getStr(): Str
    {
        if (self::$strInstance === null) {
            self::$strInstance = new Str();
        }
        return self::$strInstance;
    }

    /**
     * Convert a string to a URL-friendly slug.
     * 
     * Replaces spaces and special characters with a separator (default is '-')
     * to create a clean, URL-safe string.
     * 
     * Usage: #[$title | slug]
     * 
     * @param string $value The input string.
     * @return string The slugified string.
     */
    public static function slug(string $value): string
    {
        return self::getStr()->slug($value);
    }

    /**
     * Convert a string to camelCase.
     * 
     * Transforms a string with spaces, dashes, or underscores into camelCase.
     * Example: 'hello_world' becomes 'helloWorld'.
     * 
     * Usage: #[$string_with_spaces | camel]
     * 
     * @param string $value The input string.
     * @return string The camelCased string.
     */
    public static function camel(string $value): string
    {
        return self::getStr()->camel($value);
    }

    /**
     * Convert a string to snake_case.
     * 
     * Transforms a camelCase or StudlyCase string into snake_case.
     * Example: 'helloWorld' becomes 'hello_world'.
     * 
     * Usage: #[$camelCaseString | snake]
     * 
     * @param string $value The input string.
     * @return string The snake_cased string.
     */
    public static function snake(string $value): string
    {
        return self::getStr()->snake($value);
    }

    /**
     * Limit the number of characters in a string.
     * 
     * Truncates a string to a specified length and appends a custom ending.
     * It can preserve whole words to avoid cutting them in the middle.
     * 
     * Simple usage: #[$long_text | limit(100)]
     * Advanced usage: #[$long_text | limit(100, '...', true)]
     *
     * @param string $value The string to limit.
     * @param int $limit The maximum number of characters.
     * @param string $end The string to append if truncated.
     * @param bool $preserveWords Whether to preserve whole words.
     * @return string The truncated string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...', bool $preserveWords = true): string
    {
        return self::getStr()->limit($value, $limit, $end, $preserveWords);
    }

    /**
     * Formats a number as currency.
     * Usage: #[$price | currency('BRL')]
     * @param mixed $value
     * @param string $currency
     * @return string
     */
    public static function currency($value, string $currency = 'USD'): string
    {
        $value = (float) $value;
        $formatted = number_format($value, 2, ',', '.');

        // Basic mapping for common currencies
        $prefixes = [
            'USD' => '$ ',
            'EUR' => '€ ',
            'BRL' => 'R$ ',
        ];

        return ($prefixes[$currency] ?? $currency . ' ') . $formatted;
    }
}

// Automatic registration of the asset filter
LudouFilters::addFilter('asset', [LudouFilters::class, 'asset']);