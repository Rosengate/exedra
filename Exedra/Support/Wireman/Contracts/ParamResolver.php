<?php

namespace Exedra\Support\Wireman\Contracts;

use Exedra\Support\Wireman\Wireman;

interface ParamResolver
{
    /**
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function canResolveParam(\ReflectionParameter $param);

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     */
    public function resolveParam(\ReflectionParameter $param, Wireman $wireman);
}