<?php

namespace Exedra\Routeller\Cache;

class ArrayCache implements CacheInterface
{
    protected $storage;

    public function __construct()
    {
        $this->storage = array();
    }

    public function set($key, array $routes, $lastModified)
    {
        $this->storage[$key] = $routes;
    }

    public function get($key)
    {
        if (!isset($this->storage[$key]))
            return false;

        return $this->storage[$key];
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function clear($key)
    {
        unset($this->storage[$key]);
    }

    public function clearAll()
    {
        $this->storage = array();
    }
}