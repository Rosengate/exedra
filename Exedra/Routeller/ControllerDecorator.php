<?php

namespace Exedra\Routeller;

class ControllerDecorator
{
    /**
     * @var mixed
     */
    protected $middleware;

    /**
     * @var string
     */
    private $controller;

    /**
     * ControllerDecorator constructor.
     * @param string $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param $middleware
     * @return static
     */
    public function middleware($middleware)
    {
        $this->middleware = $middleware;

        return $this;
    }
}