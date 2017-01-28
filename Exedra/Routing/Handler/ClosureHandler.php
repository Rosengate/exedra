<?php
namespace Exedra\Routing\Handler;

use Exedra\Routing\Factory;
use Exedra\Routing\LevelHandler;
use Exedra\Routing\Route;

class ClosureHandler implements LevelHandler
{
    public function validate($pattern, Route $route = null)
    {
        if(is_object($pattern) && $pattern instanceof \Closure)
            return true;

        return false;
    }

    public function resolve(Factory $factory, $callback, Route $route = null)
    {
        $router = $factory->createLevel(array(), $route);

        $callback($router);

        return $router;
    }
}