<?php

namespace Exedra\Support\Wireful\Resolvers;

use Exedra\Container\Container;
use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Wireman;

class ContainerResolver implements WiringResolver
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function canResolveWiring($pattern)
    {
        if (!is_string($pattern))
            return false;

        return $this->container['service']->has($pattern);
    }

    public function resolveWiring($pattern, Wireman $wireman)
    {
        return $this->container->get($pattern);
    }
}