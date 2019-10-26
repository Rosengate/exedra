<?php

namespace Exedra\Support\Wireman\Resolvers;

use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Wireman;

class OptionalParamResolver implements ParamResolver
{
    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param)
    {
        return $param->isOptional();
    }

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman)
    {
        return $param->getDefaultValue();
    }
}