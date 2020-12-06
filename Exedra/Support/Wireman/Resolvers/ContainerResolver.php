<?php

namespace Exedra\Support\Wireman\Resolvers;

use Exedra\Container\Container;
use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Wireman;

class ContainerResolver implements WiringResolver, ParamResolver
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

    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param)
    {
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            if (!($type = $param->getType()))
                return false;

            return $this->container['service']->has((string) $type);
        } else {
            if (!$class = $param->getClass())
                return false;

            return $this->container['service']->has((string) $class->getName());
        }
    }

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman)
    {
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            return $this->container->get((string) $param->getType());
        } else {
            return $this->container->get($param->getClass()->getName());
        }
    }
}