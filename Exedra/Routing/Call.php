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

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return bool
     */
    public function hasDependencies()
    {
        return isset($this->properties['dependencies']);
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->properties['dependencies'];
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->callable, func_get_args());
    }
}