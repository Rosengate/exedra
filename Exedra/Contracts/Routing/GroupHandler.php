<?php
namespace Exedra\Contracts\Routing;

use Exedra\Routing\Factory;
use Exedra\Routing\Route;

interface GroupHandler
{
    public function validate($pattern, Route $route = null);

    public function resolve(Factory $factory, $pattern, Route $route = null);
}