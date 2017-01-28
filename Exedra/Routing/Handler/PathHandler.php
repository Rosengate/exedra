<?php
namespace Exedra\Routing\Handler;

use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\NotFoundException;
use Exedra\Routing\Factory;
use Exedra\Routing\LevelHandler;
use Exedra\Routing\Route;

class PathHandler implements LevelHandler
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
            throw new InvalidArgumentException('Failed to create routing level. The path ['.$path.'] must return a \Closure.');

        $level = $factory->create('level', array($factory, $route));

        $closure($level);

        return $level;
    }
}