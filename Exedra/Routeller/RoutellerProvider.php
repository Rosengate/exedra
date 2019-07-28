<?php

namespace Exedra\Routeller;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Routeller\Cache\CacheInterface;
use Exedra\Routeller\Cache\EmptyCache;

class RoutellerProvider implements Provider
{
    protected $options;

    protected $cache;

    public function __construct(CacheInterface $cache = null, array $options = array())
    {
        $this->cache = $cache ? $cache : new EmptyCache();

        $this->options = $options;
    }

    public function register(Application $app)
    {
        $app->map->factory->addGroupHandler(new Handler($app, $this->cache, $this->options));
        $app->map->factory->addExecuteHandlers(new ExecuteHandler());
    }
}