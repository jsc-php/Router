<?php

namespace JscPhp\Router;

use FilesystemIterator;
use JscPhp\Router\Attr\CRoute;

class RouteCollection
{
    private array $routes = [];

    private RouterConfig $config;

    private \Memcached $mcd;

    public function __construct(RouterConfig $config)
    {
        $this->config = $config;

        if ($config->isClassDirectoryUse()) {
            if ($config->use_memcached) {
                $m = new \Memcached();
                if (count($this->config->getMemcachedServers())) {
                    foreach ($this->config->getMemcachedServers() as $server) {
                        $m->addServer($server['host'], $server['port']);
                    }
                } else {
                    $m->addServer('localhost', 11211);
                }

                if ($rl = $m->get('jsc-route-list')) {
                    $this->routes = $rl;
                } else {
                    $this->processRouteDirectories();
                    $m->set('jsc-route-list', $this->routes, 60);
                }
            } else {
                $this->processRouteDirectories();
            }
        }
        ksort($this->routes);
    }

    private function processRouteDirectories(): void
    {
        $this->buildRoutesFromDirectory();
    }

    public function buildRoutesFromDirectory(?string $class_path = null, ?string $sub_path = null): RouteCollection
    {
        if (isset($class_path)) {
            $this->_buildRoutes($class_path, $sub_path);
        } else {
            foreach ($this->config->getClassDirectories() as $directory) {
                $this->_buildRoutes($directory[0], $directory[1]);
            }
        }
        return $this;
    }

    private function _buildRoutes(string $class_path, ?string $sub_path = null): void
    {
        //Make sure the class path ends with e Directory Separator
        $class_path = DIRECTORY_SEPARATOR . trim($class_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $dir = $class_path;
        //If a sub path is defined, add it to the $dir
        if ($sub_path && trim($sub_path) !== '') {
            $dir .= trim($sub_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        $di = new \RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($di) as $file) {
            $file_path = $file->getPathname();
            $class = str_replace($class_path, '', $file_path);
            $class = str_replace('.php', '', $class);
            $class = str_replace(DIRECTORY_SEPARATOR, '\\', $class);
            if (class_exists($class)) {
                $reflect = new \ReflectionClass($class);
                $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $attrs = $method->getAttributes(CRoute::class);
                    foreach ($attrs as $attr) {
                        $args = $attr->getArguments();
                        $pattern = $args['route'] ?? $args[0];
                        $http_method = strtolower($args['http_method'] ?? $args[1] ?? 'any');
                        $name = $args['name'] ?? $args[2] ?? $class . '\\' . $method->getName();
                        $priority = $args['priority'] ?? $args[3] ?? 999;
                        $protected = $args['protected'] ?? false;
                        $this->routeAdd($http_method, $name, $pattern, $class, $method->getName(), $priority);
                    }
                }
            }
        }
    }

    /**
     * @param string $http_method get|post
     * @param string $name Route Name
     * @param string $route_pattern Regex pattern to match against URI
     * @param string $class Class to be called when the pattern matches
     * @param string $method Class method to be called
     * @param int $priority Priority of the route
     * @param bool $protected
     * @return void
     */
    public function routeAdd(
        string $http_method,
        string $name,
        string $route_pattern,
        string $class,
        string $method,
        int $priority = 999,
        bool $protected = false
    ): void {
        $route = new Route($name, $route_pattern, $class, $method, $protected);

        $this->routes[strtolower($http_method)][$priority][] = $route;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function matchRoute(?string $uri = null, array &$matches = []): Route|false
    {
        /** @var Route $route */
        //@var $route Route
        if (empty($uri)) {
            $uri = Request::getRequestURI();
        }
        $uri = Request::normalizeURI($uri);
        if (!empty($this->routes[strtolower(Request::getRequestMethod())])) {
            foreach ($this->routes[strtolower(Request::getRequestMethod())] as $priority) {
                foreach ($priority as $route) {
                    $regex = $route->pattern;
                    if (preg_match_all($regex, $uri, $matches, PREG_SET_ORDER)) {
                        return $route;
                    }
                }
            }
        }
        if (!empty($this->routes['any'])) {
            foreach ($this->routes['any'] as $priority) {
                foreach ($priority as $route) {
                    $regex = $route->pattern;
                    if (preg_match($regex, $uri)) {
                        return $route;
                    }
                }
            }
        }
        return false;
    }

}