<?php
namespace Ludelix\Routing\Helpers;

use Ludelix\Bridge\Bridge;

class RouteHelper
{
    public static function route($name, $params = [], $absolute = true)
    {
        return Bridge::route()->url($name, $params, ['absolute' => $absolute]);
    }

    public static function route_to($controllerAction, $params = [])
    {
        return Bridge::route()->urlFromController($controllerAction, $params);
    }

    public static function redirect($to = null)
    {
        return Bridge::response()->redirect($to);
    }

    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return Bridge::response()->redirect($referer);
    }

    public static function current()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function is($pattern)
    {
        $current = self::currentRouteName();
        $pattern = str_replace('*', '.*', $pattern);
        return preg_match('#^' . $pattern . '$#', $current);
    }

    public static function has($name)
    {
        return Bridge::route()->hasRoute($name);
    }

    public static function currentRouteName()
    {
        return Bridge::route()->getCurrentRouteName();
    }

    public static function currentRouteAction()
    {
        return Bridge::route()->getCurrentRouteAction();
    }

    public static function parameters()
    {
        return Bridge::route()->getCurrentRouteParameters();
    }

    public static function fullUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
} 