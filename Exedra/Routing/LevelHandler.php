<?php
namespace Exedra\Routing;

interface LevelHandler
{
    public function validate($pattern, Route $route = null);

    public function resolve(Factory $factory, $pattern, Route $route = null);
}