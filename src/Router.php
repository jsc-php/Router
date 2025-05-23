<?php

namespace JscPhp\Router;

use JetBrains\PhpStorm\NoReturn;

class Router
{
    private RouterConfig   $config;
    private false|Route    $matched_route = false;
    public RouteCollection $routeCollection {
        get {
            return $this->routeCollection;
        }
    }

    public function __construct(?RouterConfig $config = null)
    {
        $this->config = $config ?? new RouterConfig();
        $this->routeCollection = new RouteCollection($this->config);
    }

    public function addRoute(
        string $http_method,
        string $pattern,
        string $class,
        string $method,
        string $name = '',
        int $priority =
        999,
    ): void {
        $this->routeCollection->routeAdd(
            http_method  : strtolower($http_method),
            name         : $name,
            route_pattern: $pattern,
            class        : $class,
            method       : $method,
            priority     : $priority,
        );
    }

    public function any(string $pattern, string $class, string $method, string $name = '', int $priority = 999): void
    {
        $this->routeCollection->routeAdd(
            http_method  : 'any',
            name         : $name,
            route_pattern: $pattern,
            class        : $class,
            method       : $method,
            priority     : $priority,
        );
    }

    public function get(string $pattern, string $class, string $method, string $name = '', int $priority = 999): void
    {
        $this->routeCollection->routeAdd(
            http_method  : 'get',
            name         : $name,
            route_pattern: $pattern,
            class        : $class,
            method       : $method,
            priority     : $priority,
        );
    }

    #[NoReturn]
    public function listRoutes()
    {
        echo '<pre>';
        print_r($this->routeCollection->getRoutes());
        echo '</pre>';
        exit();
    }

    public function post(string $pattern, string $class, string $method, string $name = '', int $priority = 999): void
    {
        $this->routeCollection->routeAdd(
            http_method  : 'post',
            name         : $name,
            route_pattern: $pattern,
            class        : $class,
            method       : $method,
            priority     : $priority,
        );
    }

    public function route(?string $uri = null): false|Route
    {
        if ($uri === null) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        $matches = [];
        $route = $this->routeCollection->matchRoute($uri, $matches);;
        if ($route === false) {
            return false;
        }

        //Get the variables from the URL
        $uri = Request::normalizeURI($uri);
        //$parts = explode('/', trim($uri, '/'));
        //$route_vars = $route->getVariables();
        //$route_vars = $this->_getVariables($route, $uri);
        /*$method_vars = array_map(function ($value) use ($parts) {
            return $parts[$value['position']];
        }, $route_vars);*/
        $class = $route->getClass();

        $method = $route->getMethod();

        $c = new $class();

        $variables = $route->getVariables();
        $route_vars = array_map(function ($value) use ($matches) {
            return $matches[0][$value['position']];
        }, $variables);

        call_user_func_array([$c, $method], $route_vars);
        return $route;
    }

    private function matchRoute(?string $uri = null): false|Route
    {
        if (!$uri) {
            $uri = Request::getRequestURI();
        }
        if (empty($this->matched_route)) {
            $this->matched_route = $this->routeCollection->matchRoute($uri);
        }
        return $this->matched_route;
    }

    public function getClass(?string $uri = null): false|string
    {
        /** @var false|Route $route */
        $route = $this->routeCollection->matchRoute($uri);
        if ($route instanceof Route) {
            return $route->getClass();
        }
        return false;
    }

    private function _getVariables(Route $route, string $uri): array
    {
        $ret = [];
        $variables = $route->getVariables();
        preg_match_all($route->pattern, $uri, $matches, PREG_SET_ORDER);
        foreach ($variables as $key => $value) {
            $ret[$key] = $matches[$value['position']];;
        }
        return $ret;
    }

}