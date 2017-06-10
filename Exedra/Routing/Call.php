<?php
namespace Exedra\Routing;

class Call
{
    protected $properties;

    protected $callable;

    public function __construct(callable $callable, array $properties = array())
    {
        $this->callable = $callable;

        $this->properties = $properties;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function hasDependencies()
    {
        return isset($this->properties['dependencies']);
    }

    public function getDependencies()
    {
        return $this->properties['dependencies'];
    }

    public function __invoke()
    {
        return call_user_func_array($this->callable, func_get_args());
    }
}