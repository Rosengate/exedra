<?php

namespace Exedra\Support\Wireman\Resolvers;

use Exedra\Support\BcHelper;
use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Exceptions\ParamResolveException;
use Exedra\Support\Wireman\Exceptions\WiringResolveException;
use Exedra\Support\Wireman\Wireman;

class InstantiationResolver implements WiringResolver, ParamResolver
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
            throw new WiringResolveException('Failed to resolve [' . $e->getParameter() . '] for ' . $pattern . '::' . $classRef->getConstructor()->getName());
        }

        return $classRef->newInstanceArgs($dependencies);
    }

    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param)
    {
        return BcHelper::ReflectionParamIsClass($param);
//        if (version_compare(phpversion(), '7.0.0', '>=')) {
//            return !!$param->getType();
//        } else {
//            return !!$param->getClass();
//        }
    }

    /**
     * @param \ReflectionParameter $param
     * @param Wireman $wireman
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman)
    {
        return $this->resolveWiring(BcHelper::ReflectionParamGetClass($param), $wireman);
//        $type = version_compare(phpversion(), '7.0.0', '>=') ? (string) $param->getType() : $param->getClass()->getName();
//
//        return $this->resolveWiring($type, $wireman);
    }
}