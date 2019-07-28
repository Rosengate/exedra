<?php
namespace Exedra\Routing\GroupHandlers;

use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Routing\Factory;
use Exedra\Routing\Route;

class ArrayHandler implements GroupHandler
{
    public function validateGroup($pattern, Route $route = null)
    {
        return is_array($pattern);
    }

    public function resolveGroup(Factory $factory, $routes, Route $route = null)
    {
        return $factory->createGroup($routes, $route);
    }
}