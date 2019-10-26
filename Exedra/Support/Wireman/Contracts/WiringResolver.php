<?php

namespace Exedra\Support\Wireman\Contracts;

use Exedra\Support\Wireman\Wireman;

interface WiringResolver
{
    /**
     * @param $pattern
     * @return bool
     */
    public function canResolveWiring($pattern);

    /**
     * @param $pattern
     * @param Wireman $wireman
     * @return mixed
     */
    public function resolveWiring($pattern, Wireman $wireman);
}