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
     * @var string $lookupPath
     */
    protected $lookupPath;

    public function __construct($path)
    {
        $this->lookupPath = $path;
    }

    /**
     * @param $pattern
     * @param Route|null $route
     * @return bool
     */
    public function validateGroup($pattern, Route $route = null)
    {
        if (is_string($pattern))
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
    public function resolveGroup(Factory $factory, $path, Route $route = null)
    {
        $path = $this->lookupPath . '/' . ltrim($path, '/\\');

        if (!file_exists($path))
            throw new NotFoundException('File [' . $path . '] does not exists.');

        $closure = require $path;

        // expecting a \Closure from this loaded file.
        if (!($closure instanceof \Closure))
            throw new InvalidArgumentException('Failed to create routing group. The path [' . $path . '] must return a \Closure.');

        $group = $factory->createGroup(array(), $route);

        $closure($group);

        return $group;
    }
}