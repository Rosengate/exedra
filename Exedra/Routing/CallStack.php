<?php
namespace Exedra\Routing;

class CallStack
{
    protected $stacks;

    public function __construct(array $stacks)
    {
        $this->stacks = $stacks;
    }

    public function removeCall($name)
    {
        if(isset($this->stacks[$name]))
            unset($this->stacks[$name]);

        return $this;
    }

    /**
     * Create a generic $next caller
     * @return \Closure
     */
    public function getNextCaller()
    {
        $callStack = $this;

        $next = function() use($callStack, &$next)
        {
            $args = func_get_args();

            $args[] = $next;

            return call_user_func_array(array($callStack, 'next'), $args);
        };

        return $next;
    }

    /**
     * @return mixed
     */
    public function getNextCall()
    {
        return next($this->stacks);
    }

    /**
     * Initiate the first call
     * @return mixed
     */
    public function call()
    {
        reset($this->stacks);

        return call_user_func_array(current($this->stacks), func_get_args());
    }

    /**
     * Call the current stack and point to the next stack
     * @return mixed
     */
    public function next()
    {
        return call_user_func_array(next($this->stacks), func_get_args());
    }

    /**
     * Alias to call()
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array(array($this, 'call'), func_get_args());
    }
}