<?php

namespace Exedra\Contracts\Routing;

interface ExecuteHandler
{
    /**
     * Validate given execute handler pattern
     * @param mixed $pattern
     * @return boolean
     */
    public function validateHandle($pattern);

    /**
     * Resolve handle into Closure or callable
     * @param string $pattern
     * @return \Closure|callable
     */
    public function resolveHandle($pattern);
}