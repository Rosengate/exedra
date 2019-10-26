<?php

namespace Exedra\Support\Wireful\Resolvers;

use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Wireman;

class CallableResolver implements WiringResolver
{
    public function canResolveWiring($pattern)
    {
        return is_callable($pattern);
    }

    public function resolve($pattern, Wireman $wireman)
    {
        return call_user_func_array($pattern, $wireman->resolveMethod(new \ReflectionMethod($wireman)));
    }
}