<?php
namespace App;

use Exedra\Contracts\Routing\RoutingHandler;
use Exedra\Routing\Factory;
use Exedra\Routing\Group;
use Exedra\Routing\Route;
use Exedra\Runtime\Context;

class MyRoutingHandler implements RoutingHandler
{
    /**
     * @param $pattern
     * @param Route|null $route
     * @return boolean
     */
    public function validateGroup($pattern, Route $route = null)
    {
        return strpos($pattern, 'paths=') === 0;
    }

    /**
     * @param Factory $factory
     * @param $pattern
     * @param Route|null $parentRoute
     * @return Group
     */
    public function resolveGroup(Factory $factory, $pattern, Route $parentRoute = null)
    {
        $paths = explode(',', str_replace('paths=', '', $pattern));

        $group = new Group($factory, $parentRoute);

        foreach ($paths as $path) {
            $group[$path]->any('/' . $path)->execute('path=' . $path);
        }

        return $group;
    }

    /**
     * @param string $pattern
     * @return bool
     */
    public function validateHandle($pattern)
    {
        return strpos($pattern, 'path=') === 0;
    }

    /**
     * @param string $pattern
     * @return \Closure|callable
     */
    public function resolveHandle($pattern)
    {
        $path = str_replace('path=', '', $pattern);

        return function(Context $context) use ($path) {
            return $path;
        };
    }
}