<?php
use Ludelix\Routing\Helpers\RouteHelper;

if (!function_exists('route')) {
    function route($name, $params = [], $absolute = true) {
        return RouteHelper::route($name, $params, $absolute);
    }
}
if (!function_exists('route_to')) {
    function route_to($controllerAction, $params = []) {
        return RouteHelper::route_to($controllerAction, $params);
    }
}
if (!function_exists('redirect')) {
    function redirect($to = null) {
        return RouteHelper::redirect($to);
    }
}
if (!function_exists('back')) {
    function back() {
        return RouteHelper::back();
    }
}
if (!function_exists('current')) {
    function current() {
        return RouteHelper::current();
    }
}
if (!function_exists('route_is')) {
    function route_is($pattern) {
        return RouteHelper::is($pattern);
    }
}
if (!function_exists('route_has')) {
    function route_has($name) {
        return RouteHelper::has($name);
    }
}
if (!function_exists('current_route_name')) {
    function current_route_name() {
        return RouteHelper::currentRouteName();
    }
}
if (!function_exists('current_route_action')) {
    function current_route_action() {
        return RouteHelper::currentRouteAction();
    }
}
if (!function_exists('route_parameters')) {
    function route_parameters() {
        return RouteHelper::parameters();
    }
}
if (!function_exists('full_url')) {
    function full_url() {
        return RouteHelper::fullUrl();
    }
} 