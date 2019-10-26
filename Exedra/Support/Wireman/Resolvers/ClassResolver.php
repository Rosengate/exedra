<?php

namespace Exedra\Support\Wireful\Resolvers;

use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Exceptions\ParamResolveException;
use Exedra\Support\Wireman\Exceptions\WiringResolveException;
use Exedra\Support\Wireman\Wireman;

class ClassResolver implements WiringResolver, ParamResolver
{
    public function canResolveWiring($pattern)
    {
        return is_string($pattern) && class_exists($pattern);
    }

    public function resolveWiring($pattern, Wireman $wireman)
    {
        $classRef = new \ReflectionClass($pattern);

        try {
            $dependencies = $wireman->resolveMethod($classRef->getConstructor());
        } catch (ParamResolveException $e) {
            throw new WiringResolveException('Failed to resolve [' . $e->getMessage() . '] for ' . $pattern . '::' . $classRef->getConstructor()->getName());
        }

        return $classRef->newInstanceArgs($dependencies);
    }

    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param)
    {
        return !!$param->getClass();
    }

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman)
    {
        return $this->resolveWiring($param->getClass(), $wireman);
    }
}