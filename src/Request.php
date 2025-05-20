<?php

namespace JscPhp\Router;

class Request
{
    private function __construct() {}

    public static function getDocumentRoot(): string
    {
        return filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
    }

    public static function getRequestMethod(): string
    {
        return strtoupper(filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
    }

    /**Normalize the URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function normalizeURI(?string $uri = null): string
    {
        $uri = $uri ?? self::getRequestURI();
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        if (str_contains($uri, '#')) {
            $uri = substr($uri, 0, strpos($uri, '#'));
        }
        $uri = trim($uri, '/');
        /*$parts = explode('/', $route);
        $parts = array_map(function ($item) {
            if (str_contains($item, '|')) {
                $item = substr($item, 0, strpos($item, '|'));
            }
            return $item;
        }, $parts);
        $route = implode('/', $parts);*/
        return '/' . $uri . '/';
    }

    public static function getRequestURI(): string
    {
        return filter_input(INPUT_SERVER, 'REQUEST_URI');
    }
}