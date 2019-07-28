<?php

namespace Exedra\Routing\ExecuteHandlers;

use Exedra\Contracts\Routing\ExecuteHandler;

class ClosureHandler implements ExecuteHandler
{
    public function validateHandle($pattern)
    {
        if ($pattern instanceof \Closure)
            return true;

        return false;
    }

    public function resolveHandle($pattern)
    {
        return $pattern;
    }
}