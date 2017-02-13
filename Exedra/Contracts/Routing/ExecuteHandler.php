<?php
namespace Exedra\Contracts\Routing;

interface ExecuteHandler
{
    /**
     * Validate given handler pattern
     * @param mixed $pattern
     * @return boolean
     */
    public function validate($pattern);

    /**
     * Resolve into Closure or callable
     * @return \Closure|callable
     */
    public function resolve($pattern);
}