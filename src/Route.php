<?php

namespace JscPhp\Router;

class Route
{
    private string $name;
    private string $route;
    private string $class;
    private string $method;
    private bool $protected = false;

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected): Route
    {
        $this->protected = $protected;
        return $this;
    }
    private array $variables = [];
    public string $pattern {
        get {
            return $this->pattern;
        }
    }


    public function __construct(string $name, string $route, string $class, string $method, bool $protected = false)
    {
        $this->name = $name;
        $this->route = $route;
        $this->class = $class;
        $this->method = $method;
        $this->pattern = $this->buildRegexPattern();
        $this->protected = $protected;
    }

    public function buildRegexPattern(?string $route = null): string
    {
        if (!$route) {
            $route = $this->route;
        }
        $regex = '/^';
        $variables = [];
        $route = trim($route, '/');
        $route = explode('/', $route);
        for ($i = 0; $i < count($route); $i++) {
            $segment = $route[$i];
            if (str_starts_with($segment, ':')) {
                $position = $i + 1;
                $parts = explode('|', trim($segment, ':'));
                $r = match ($parts[count($parts) - 1]) {
                    'b64' => '([\w\/+=]+)',
                    'd', 'decimal' => '(?":\d+.\d+)',
                    '#' => '(\d+)',
                    'DIGITS', 'r', 'R' => '([0-9]+)',
                    default => '(\w+)',
                };
                if (count($parts) === 1) {
                    $variables[] = [
                        'position' => $position,
                        'regex' => $r,
                    ];
                } else {
                    $variables[$parts[0]] = [
                        'position' => $position,
                        'regex' => $r,
                    ];
                }
                $regex .= '\/' . $r;
            } else {
                $regex .= '\/(' . $segment . ')';
            }
        }
        $this->variables = $variables;
        $regex .= '\/$/';
        return $regex;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function processMatchedURI(string $uri): void
    {
        preg_match($this->pattern, $uri, $matches);
    }


}