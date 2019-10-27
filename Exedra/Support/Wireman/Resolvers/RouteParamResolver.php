<?php

namespace Exedra\Support\Wireman\Resolvers;

use Exedra\Runtime\Context;
use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Wireman;

class RouteParamResolver implements ParamResolver
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param)
    {
        if ($param->getClass() || $param->isArray())
            return false;

        return true;
    }

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman)
    {
        return $this->context->param($param->getName(), $param->isOptional() ? $param->getDefaultValue() : null);
    }
}