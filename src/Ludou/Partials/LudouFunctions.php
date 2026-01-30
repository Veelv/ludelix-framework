<?php

namespace Ludelix\Ludou\Partials;

use Ludelix\Core\Support\Str;
use Ludelix\Core\Support\Uuid;

class LudouFunctions
{
    private static $functions = [];
    private static $customFunctions = [];
    private static $macros = [];

    public static function getDefaultFunctions(): array
    {
        return [
            'range' => [self::class, 'range'],
            'count' => [self::class, 'count'],
            'merge' => [self::class, 'merge'],
            'sort' => [self::class, 'sort'],
            'filter' => [self::class, 'filter'],
            'keys' => [self::class, 'keys'],
            'values' => [self::class, 'values'],
            'now' => [self::class, 'now'],
            'date' => [self::class, 'date'],
            'min' => [self::class, 'min'],
            'max' => [self::class, 'max'],
            'random' => [self::class, 'random'],
            'sum' => [self::class, 'sum'],
            'avg' => [self::class, 'avg'],
            'empty' => [self::class, 'isEmpty'],
            'inArray' => [self::class, 'inArray'],
            'isNumeric' => [self::class, 'isNumeric'],
            'isString' => [self::class, 'isString'],
            'isArray' => [self::class, 'isArray'],
            'startsWith' => [self::class, 'startsWith'],
            'endsWith' => [self::class, 'endsWith'],
            'split' => [self::class, 'split'],
            'length' => [self::class, 'length'],
            'json' => [self::class, 'json'],
            'type' => [self::class, 'getType'],
            't' => [self::class, 't'],
            'route' => [self::class, 'route'],
            'route_to' => [self::class, 'route_to'],
            'redirect' => [self::class, 'redirect'],
            'back' => [self::class, 'back'],
            'current' => [self::class, 'current'],
            'route_is' => [self::class, 'route_is'],
            'route_has' => [self::class, 'route_has'],
            'current_route_name' => [self::class, 'current_route_name'],
            'current_route_action' => [self::class, 'current_route_action'],
            'route_parameters' => [self::class, 'route_parameters'],
            'full_url' => [self::class, 'full_url'],
            'locale' => [self::class, 'locale'],
            'config' => [self::class, 'config'],
            'classNames' => [self::class, 'classNames'],
        ];
    }

    public static function addFunctions(array $functions)
    {
        foreach ($functions as $name => $callback) {
            self::$functions[$name] = $callback;
        }
    }

    public static function apply(string $functionName, ...$args)
    {
        // Registered PHP macro
        if (isset(self::$macros[$functionName])) {
            return call_user_func_array(self::$macros[$functionName], $args);
        }

        // Custom function registered at runtime
        if (isset(self::$customFunctions[$functionName])) {
            return call_user_func_array(self::$customFunctions[$functionName], $args);
        }
        // First, try to apply a custom function
        if (class_exists('Ludelix\\Ludou\\Partials\\LudouExtensions') && LudouExtensions::hasFunction($functionName)) {
            $processedArgs = array_map(function ($arg) {
                if (is_string($arg) && strpos($arg, '.') !== false) {
                    return (new LudouExpressionProcessor())->resolveValue($arg);
                }
                return $arg;
            }, $args);
            return LudouExtensions::applyFunction($functionName, ...$processedArgs);
        }
        // Then check default functions
        $functions = self::getDefaultFunctions();
        if (isset($functions[$functionName])) {
            return call_user_func_array($functions[$functionName], $args);
        }
        return null;
    }

    public static function registerCustomFunction(string $name, callable $callback): void
    {
        self::$customFunctions[$name] = $callback;
    }

    public static function registerMacro(string $name, callable $callback): void
    {
        self::$macros[$name] = $callback;
    }

    /**
     * Adapts functions to accept named arguments.
     * @param array $args
     * @param array $names
     * @param array $defaults
     * @return array
     */
    private static function extractNamedArgs(array &$args, array $names, $defaults = []): array
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

    /**
     * @param array $arr
     * @return bool
     */
    private static function isAssoc(array $arr): bool
    {
        foreach (array_keys($arr) as $k)
            if (!is_int($k))
                return true;
        return false;
    }

    /**
     * Creates a new array containing a range of elements.
     * Usage: #[range(1, 10, 2)]
     * @param mixed ...$args
     * @return array
     */
    public static function range(...$args): array
    {
        $opt = self::extractNamedArgs($args, ['start', 'end', 'step'], ['start' => 1, 'end' => 10, 'step' => 1]);
        return range($opt['start'], $opt['end'], $opt['step']);
    }

    /**
     * Formats a date.
     * Usage: #[$myDate | date('d/m/Y')]
     * @param mixed $date
     * @param mixed ...$args
     * @return string
     */
    public static function date($date, ...$args): string
    {
        $opt = self::extractNamedArgs($args, ['format'], ['format' => 'Y-m-d H:i:s']);
        if ($date instanceof \DateTime) {
            return $date->format($opt['format']);
        }
        return date($opt['format'], is_numeric($date) ? $date : strtotime($date));
    }

    /**
     * Counts the number of items in a variable.
     * For strings, it returns the character length. For arrays, the element count.
     * Usage: #[count($myArray)]
     * @param mixed $value
     * @return int
     */
    public static function count($value): int
    {
        // If the variable is not set, return 0
        if (!isset($value)) {
            return 0;
        }
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }
        if (is_string($value)) {
            return mb_strlen($value);
        }
        return 0;
    }

    /**
     * Merges one or more arrays.
     * Usage: #[merge($arr1, $arr2)]
     * @param mixed ...$arrays
     * @return array
     */
    public static function merge(...$arrays): array
    {
        return array_merge(...$arrays);
    }

    /**
     * Sorts an array.
     * Usage: #[$myArray | sort] or #[$myArray | sort(true)] for reverse
     * @param array $array
     * @param bool $reverse
     * @return array
     */
    public static function sort(array $array, bool $reverse = false): array
    {
        if ($reverse) {
            rsort($array);
        } else {
            sort($array);
        }
        return $array;
    }

    /**
     * Filters elements of an array using a callback function (if provided) or removes empty values.
     * Note: Callback usage is advanced and depends on context.
     * Usage: #[$myArray | filter]
     * @param array $array
     * @return array
     */
    public static function filter(array $array): array
    {
        return array_filter($array);
    }

    /**
     * Returns all the keys of an array.
     * Usage: #[keys($myArray)]
     * @param array $array
     * @return array
     */
    public static function keys(array $array): array
    {
        return array_keys($array);
    }

    /**
     * Returns all the values of an array.
     * Usage: #[values($myArray)]
     * @param array $array
     * @return array
     */
    public static function values(array $array): array
    {
        return array_values($array);
    }

    /**
     * Gets the current date and time, formatted.
     * Usage: #[now('d/m/Y')]
     * @param string $format
     * @return string
     */
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }

    /**
     * Finds the lowest value among the arguments.
     * Usage: #[min(1, 2, 3)]
     * @param mixed $value
     * @param mixed ...$values
     * @return mixed
     */
    public static function min($value, ...$values): mixed
    {
        return min($value, ...$values);
    }

    /**
     * Finds the highest value among the arguments.
     * Usage: #[max(1, 2, 3)]
     * @param mixed $value
     * @param mixed ...$values
     * @return mixed
     */
    public static function max($value, ...$values): mixed
    {
        return max($value, ...$values);
    }

    /**
     * Gets a random value from an array.
     * Usage: #[$myArray | random]
     * @param mixed $array
     * @return mixed
     */
    public static function random($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        return $array[array_rand($array)];
    }

    /**
     * Calculates the sum of values in an array.
     * Usage: #[sum($myNumbers)]
     * @param array $array
     * @return float
     */
    public static function sum(array $array): float
    {
        return array_sum($array);
    }

    /**
     * Calculates the average of values in an array.
     * Usage: #[avg($myNumbers)]
     * @param array $array
     * @return float
     */
    public static function avg(array $array): float
    {
        return count($array) ? array_sum($array) / count($array) : 0;
    }

    /**
     * Checks if a variable is empty.
     * Usage: #[empty($myVar)]
     * @param mixed $value
     * @return bool
     */
    public static function isEmpty($value): bool
    {
        return empty($value);
    }

    /**
     * Checks if a value exists in an array.
     * Usage: #[inArray('needle', $haystack)]
     * @param mixed $needle
     * @param array $haystack
     * @return bool
     */
    public static function inArray($needle, array $haystack): bool
    {
        return in_array($needle, $haystack);
    }

    /**
     * Checks if a variable is numeric.
     * Usage: #[isNumeric($myVar)]
     * @param mixed $value
     * @return bool
     */
    public static function isNumeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Checks if a variable is a string.
     * Usage: #[isString($myVar)]
     * @param mixed $value
     * @return bool
     */
    public static function isString($value): bool
    {
        return is_string($value);
    }

    /**
     * Checks if a variable is an array.
     * Usage: #[isArray($myVar)]
     * @param mixed $value
     * @return bool
     */
    public static function isArray($value): bool
    {
        return is_array($value);
    }

    /**
     * Checks if a string starts with a given substring.
     * Usage: #[startsWith($myString, 'prefix')]
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Checks if a string ends with a given substring.
     * Usage: #[endsWith($myString, 'suffix')]
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Splits a string by a string.
     * Usage: #["a,b,c" | split(',')]
     * @param string $value
     * @param string $delimiter
     * @return array
     */
    public static function split(string $value, string $delimiter = ''): array
    {
        return explode($delimiter, $value);
    }

    /**
     * Returns the length of a string or the count of an array.
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
     * Converts a value to its JSON representation.
     * Usage: #[$myArray | json]
     * @param mixed $value
     * @return string
     */
    public static function json($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gets the type of a variable.
     * Usage: #[type($myVar)]
     * @param mixed $value
     * @return string
     */
    public static function getType($value): string
    {
        return gettype($value);
    }

    /**
     * Gets a translation string.
     * Usage: #[t('messages.welcome')]
     * @param mixed $key
     * @param array $args
     * @param null $locale
     * @return mixed
     */
    public static function t($key, $args = [], $locale = null)
    {
        // If Translation service exists, use it
        try {
            if (class_exists('Ludelix\\Translation\\Translation')) {
                $translator = \Ludelix\Translation\Translation::instance();
                if ($translator) {
                    return $translator->get($key, $args, $locale);
                }
            }
        } catch (\Throwable $e) {
        }
        // Fallback to filesystem
        static $cache = [];
        $fw = \Ludelix\Core\Framework::getInstance();
        $basePath = $fw ? $fw->basePath() : dirname(__DIR__, 6);
        $appConfig = file_exists($basePath . '/config/app.php') ? include $basePath . '/config/app.php' : [];
        $locale = $locale ?: ($appConfig['locale'] ?? 'en');
        $fallback = $appConfig['fallback_locale'] ?? 'en';
        $langDir = $basePath . '/frontend/lang/' . $locale;
        $files = glob($langDir . '/*.{php,json}', GLOB_BRACE);
        $data = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.php')) {
                $data = array_merge($data, include $file);
            } elseif (str_ends_with($file, '.json')) {
                $data = array_merge($data, json_decode(file_get_contents($file), true));
            }
        }
        if (!$data && $fallback && $fallback !== $locale) {
            $langDir = $basePath . '/frontend/lang/' . $fallback;
            $files = glob($langDir . '/*.{php,json}', GLOB_BRACE);
            foreach ($files as $file) {
                if (str_ends_with($file, '.php')) {
                    $data = array_merge($data, include $file);
                } elseif (str_ends_with($file, '.json')) {
                    $data = array_merge($data, json_decode(file_get_contents($file), true));
                }
            }
        }
        if (!$data)
            return $key;
        $parts = explode('.', $key);
        $val = $data;
        foreach ($parts as $p) {
            if (is_array($val) && isset($val[$p])) {
                $val = $val[$p];
            } else {
                return $key;
            }
        }
        if (is_string($val) && is_array($args)) {
            foreach ($args as $k => $v) {
                if (is_callable($v))
                    $v = $v();
                $val = str_replace(':' . $k, $v, $val);
            }
        }
        return $val;
    }

    /**
     * A utility function to apply a translation from any PHP context.
     * @param mixed $key
     * @param array $args
     * @param null $locale
     * @return mixed
     */
    public static function applyTranslation($key, $args = [], $locale = null)
    {
        return self::t($key, $args, $locale);
    }

    /**
     * Generates a URL for an asset.
     * Usage: #[asset('css/app.css')]
     * @param mixed ...$args
     * @return string
     */
    public static function asset(...$args)
    {
        // Join all arguments into a single path
        $allParts = [];
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

        // Detect the base URL (e.g., /myproject)
        $base = '/';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            if ($base === '' || $base === '\\' || $base === '/') {
                $base = '';
            }
        }

        return $base . '/' . $cleanPath;
    }

    // Métodos helpers de rota nativos
    /**
     * Generates a URL for a named route.
     * Usage: #[route('home')]
     * @param mixed $name
     * @param array $params
     * @param bool $absolute
     * @return mixed
     */
    public static function route($name, $params = [], $absolute = true)
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::route($name, $params, $absolute);
        } catch (\Throwable $e) {
            $fallback = '/' . str_replace('.', '/', $name);
            if (!empty($params)) {
                $fallback .= '/' . implode('/', array_map('urlencode', $params));
            }
            return $fallback;
        }
    }
    /**
     * Generates a URL to a controller action.
     * Usage: #[route_to('HomeController@index')]
     * @param mixed $controllerAction
     * @param array $params
     * @return mixed
     */
    public static function route_to($controllerAction, $params = [])
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::route_to($controllerAction, $params);
        } catch (\Throwable $e) {
            $fallback = '/' . str_replace('@', '/', $controllerAction);
            if (!empty($params)) {
                $fallback .= '/' . implode('/', array_map('urlencode', $params));
            }
            return $fallback;
        }
    }
    /**
     * Creates a redirect response.
     * Usage: #[redirect('/home')]
     * @param null $to
     * @return mixed
     */
    public static function redirect($to = null)
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::redirect($to);
        } catch (\Throwable $e) {
            return '/';
        }
    }
    /**
     * Creates a redirect response to the previous location.
     * Usage: #[back()]
     * @return mixed
     */
    public static function back()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::back();
        } catch (\Throwable $e) {
            return '/';
        }
    }
    /**
     * Gets the current URL.
     * Usage: #[current()]
     * @return mixed
     */
    public static function current()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::current();
        } catch (\Throwable $e) {
            return '/';
        }
    }
    /**
     * Checks if the current route matches a given pattern.
     * Usage: #[route_is('admin.*')]
     * @param mixed $pattern
     * @return mixed
     */
    public static function route_is($pattern)
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::is($pattern);
        } catch (\Throwable $e) {
            return false;
        }
    }
    /**
     * Checks if a named route exists.
     * Usage: #[route_has('home')]
     * @param mixed $name
     * @return mixed
     */
    public static function route_has($name)
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::has($name);
        } catch (\Throwable $e) {
            return false;
        }
    }
    /**
     * Gets the name of the current route.
     * Usage: #[current_route_name()]
     * @return mixed
     */
    public static function current_route_name()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::currentRouteName();
        } catch (\Throwable $e) {
            return '';
        }
    }
    /**
     * Gets the action of the current route.
     * Usage: #[current_route_action()]
     * @return mixed
     */
    public static function current_route_action()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::currentRouteAction();
        } catch (\Throwable $e) {
            return '';
        }
    }
    /**
     * Gets the parameters of the current route.
     * Usage: #[route_parameters()]
     * @return mixed
     */
    public static function route_parameters()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::parameters();
        } catch (\Throwable $e) {
            return [];
        }
    }
    /**
     * Gets the full URL for the current request.
     * Usage: #[full_url()]
     * @return mixed
     */
    public static function full_url()
    {
        try {
            return \Ludelix\Routing\Helpers\RouteHelper::fullUrl();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Generate a version 4 UUID.
     * 
     * Creates a universally unique identifier (UUID) that can be used for
     * database keys, unique identifiers, etc.
     * 
     * Template usage: #[uuid()]
     * 
     * @return string The generated UUID.
     */
    public static function uuid(): string
    {
        return Uuid::generate();
    }

    /**
     * Generate a random string of a given length.
     * 
     * Creates a cryptographically secure, random string that can be used for
     * tokens, passwords, or any other purpose requiring random data.
     * 
     * Template usage: #[str_random(10)]
     * 
     * @param int $length The desired length of the random string.
     * @return string The generated random string.
     */
    public static function str_random(int $length = 16): string
    {
        $str = new Str();
        return $str->random($length);
    }

    /**
     * Get current locale from translation service
     * 
     * Returns locale in format compatible with HTML lang attribute (e.g., 'pt-BR')
     * 
     * Template usage: <html lang="#[locale()]">
     * 
     * @return string The current locale
     */
    public static function locale(): string
    {
        try {
            // Try to get from Bridge translation service
            if (class_exists('\Ludelix\Bridge\Bridge')) {
                $bridge = \Ludelix\Bridge\Bridge::instance();
                if ($bridge->has('translation')) {
                    $translator = $bridge->get('translation');
                    $locale = $translator->getLocale();
                    // Convert underscore to dash (en_US -> en-US)
                    return str_replace('_', '-', $locale);
                }
            }

            // Fallback to config
            if (class_exists('\Ludelix\Core\Framework')) {
                $fw = \Ludelix\Core\Framework::getInstance();
                if ($fw) {
                    $basePath = $fw->basePath();
                    $appConfig = file_exists($basePath . '/config/app.php') ? include $basePath . '/config/app.php' : [];
                    $locale = $appConfig['locale'] ?? 'en';
                    return str_replace('_', '-', $locale);
                }
            }
        } catch (\Throwable $e) {
        }

        return 'en';
    }

    /**
     * Get configuration value
     * 
     * Template usage: #[config('app.name', 'Default')]
     * 
     * @param string $key Configuration key (e.g., 'app.name')
     * @param mixed $default Default value if not found
     * @return mixed The configuration value
     */
    public static function config(string $key, $default = null)
    {
        try {
            // Try to get from Bridge config service
            if (class_exists('\Ludelix\Bridge\Bridge')) {
                $bridge = \Ludelix\Bridge\Bridge::instance();
                if ($bridge->has('config')) {
                    $config = $bridge->get('config');
                    return $config->get($key, $default);
                }
            }

            // Fallback to direct file access
            if (class_exists('\Ludelix\Core\Framework')) {
                $fw = \Ludelix\Core\Framework::getInstance();
                if ($fw) {
                    $basePath = $fw->basePath();
                    $parts = explode('.', $key);
                    $file = array_shift($parts);
                    $configFile = $basePath . '/config/' . $file . '.php';

                    if (file_exists($configFile)) {
                        $configData = include $configFile;
                        $value = $configData;

                        foreach ($parts as $part) {
                            if (is_array($value) && isset($value[$part])) {
                                $value = $value[$part];
                            } else {
                                return $default;
                            }
                        }

                        return $value;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $default;
    }

    /**
     * Generate conditional class names
     * 
     * Template usage: #[class ['dark' => $isDark, 'light' => !$isDark]]
     * 
     * @param array $classes Associative array of class => condition
     * @return string Space-separated class names
     */
    public static function classNames(array $classes): string
    {
        $result = [];

        foreach ($classes as $class => $condition) {
            // If numeric key, it's just a class name
            if (is_numeric($class)) {
                $result[] = $condition;
            }
            // If condition is true, add the class
            elseif ($condition) {
                $result[] = $class;
            }
        }

        return implode(' ', $result);
    }
}