<?php
namespace Exedra\Routeller\Cache;

interface CacheInterface
{
    public function set($key, array $entries, $lastModified);

    public function get($key);

    public function clear($key);

    public function clearAll();
}