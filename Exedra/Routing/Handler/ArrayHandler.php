<?php
namespace Exedra\Routing\Handler;

use Exedra\Routing\Factory;
use Exedra\Routing\LevelHandler;
use Exedra\Routing\Route;

class ArrayHandler implements LevelHandler
{
    public function validate($pattern, Route $route = null)
    {
        return is_array($pattern);
    }

    public function resolve(Factory $factory, $routes, Route $route = null)
    {
        return $factory->createLevel($routes, $route);
    }
}