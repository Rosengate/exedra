<?php
namespace Exedra\Routing\ExecuteHandlers;

use Exedra\Contracts\Routing\ExecuteHandler;

class ClosureHandler implements ExecuteHandler
{
    public function validate($pattern)
    {
        if($pattern instanceof \Closure)
            return true;

        return false;
    }

    public function resolve($pattern)
    {
        return $pattern;
    }
}