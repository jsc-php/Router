<?php

namespace JscPhp\Router\Attr;


#[\Attribute]
class CRoute
{
    public function __construct(
        string $route,
        string $http_method = 'any',
        ?string $name = null,
        int $priority =        0,
        bool $protected = false
    ) {}
}