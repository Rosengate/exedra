<?php

namespace Exedra\Routeller\Cache;

class EmptyCache implements CacheInterface
{
    public function set($key, array $routes, $lastModified)
    {
        return null;
    }

    public function get($key)
    {
        return null;
    }

    public function clear($key)
    {
    }

    public function clearAll()
    {
    }
}