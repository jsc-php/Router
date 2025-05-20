<?php

namespace JscPhp\Router;

class RouterConfig
{
    private array       $memcached_servers   = [];
    private array       $class_directories   = [];
    private bool        $class_directory_use = false;
    private(set) string $version             = '1.0.0';
    public int          $cache_ttl           = 60 {
        set(int $ttl) {
            $this->cache_ttl = $ttl;
        }
    }
    public bool         $use_memcached       = false {
        set (bool $use) {
            $this->use_memcached = $use;
        }
    }

    public function __construct() {}

    public function addClassDirectory(string $class_directory, string $sub_path = ''): RouterConfig
    {
        $this->class_directories[] = [$class_directory, $sub_path];
        return $this;
    }

    public function addMemcachedServer(string $memcached_host = 'localhost', $port = 11211): void
    {
        $this->memcached_servers[] = ['host' => $memcached_host, 'port' => $port];
    }

    public function getClassDirectories(): array
    {
        return $this->class_directories;
    }

    public function getMemcachedServers(): array
    {
        return $this->memcached_servers;
    }

    public function isClassDirectoryUse(): bool
    {
        return $this->class_directory_use;
    }

    public function processClassDirectories(bool $class_directory_use = true): RouterConfig
    {
        $this->class_directory_use = $class_directory_use;
        return $this;
    }

}