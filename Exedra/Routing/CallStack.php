<?php

namespace Exedra\Routing;

class CallStack
{
    protected $callables;

    protected $initiated = false;

    /**
     * @param callable $callable
     * @param array $properties
     * @deprecated
     * @return $this
     */
    public function addCallable(callable $callable, array $properties = array())
    {
        $this->callables[] = new Call($callable, $properties);

        return $this;
    }

    /**
     * @param Call $call
     * @return $this
     */
    public function addCall(Call $call)
    {
        $this->callables[] = $call;

        return $this;
    }

    public function reset()
    {
        $this->initiated = false;

        reset($this->callables);
    }

    /**
     * @param $name
     * @return $this
     */
    public function removeCallable($name)
    {
        if (isset($this->callables[$name]))
            unset($this->callables[$name]);

        return $this;
    }

    /**
     * Create a generic $next caller
     * @return \Closure
     */
    public function getNextCaller()
    {
        $callStack = $this;

        $next = function () use ($callStack, &$next) {
            $args = func_get_args();

            $args[] = $next;

            return call_user_func_array(array($callStack, 'next'), $args);
        };

        return $next;
    }

    /**
     * Get the next call, and move the pointer
     * If movement hasn't initiated, get current
     * @return Call
     */
    public function getNextCallable()
    {
        if (!$this->initiated) {
            reset($this->callables);

            $this->initiated = true;

            return current($this->callables);
        }

        return next($this->callables);
    }

    /**
     * Call the current stack and point to the next stack
     * @return mixed
     */
    public function next()
    {
        if (!$this->initiated) {
            reset($this->callables);

            $this->initiated = true;

            return call_user_func_array(current($this->callables), func_get_args());
        }

        return call_user_func_array(next($this->callables), func_get_args());
    }

    /**
     * Alias to call()
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array(array($this, 'next'), func_get_args());
    }
}