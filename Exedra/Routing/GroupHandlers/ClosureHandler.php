<?php
namespace Exedra\Routing\GroupHandlers;

use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Routing\Factory;
use Exedra\Routing\Route;

class ClosureHandler implements GroupHandler
{
    public function validateGroup($pattern, Route $route = null)
    {
        if(is_object($pattern) && $pattern instanceof \Closure)
            return true;

        return false;
    }

    public function resolveGroup(Factory $factory, $callback, Route $route = null)
    {
        $router = $factory->createGroup(array(), $route);

        $callback($router);

        return $router;
    }
}