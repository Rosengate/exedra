<?php
namespace Exedra\Routing\GroupHandlers;

use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\NotFoundException;
use Exedra\Routing\Factory;
use Exedra\Routing\Route;

class PathHandler implements GroupHandler
{
    /**
     * @param $pattern
     * @param Route|null $route
     * @return bool
     */
    public function validate($pattern, Route $route = null)
    {
        if(is_string($pattern))
            return true;

        return false;
    }

    /**
     * @param Factory $factory
     * @param $path
     * @param Route|null $route
     * @return mixed
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function resolve(Factory $factory, $path, Route $route = null)
    {
        $path = $factory->getLookupPath() . '/' . ltrim($path, '/\\');

        if(!file_exists($path))
            throw new NotFoundException('File ['.$path.'] does not exists.');

        $closure = require $path;

        // expecting a \Closure from this loaded file.
        if(!($closure instanceof \Closure))
            throw new InvalidArgumentException('Failed to create routing group. The path ['.$path.'] must return a \Closure.');

        $group = $factory->createGroup(array(), $route);

        $closure($group);

        return $group;
    }
}