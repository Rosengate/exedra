<?php

namespace Exedra\Routeller;

use Exedra\Routeller\Controller\Controller;

class ExecuteHandler implements \Exedra\Contracts\Routing\ExecuteHandler
{
    /**
     * Validate given handler pattern
     * @param mixed $pattern
     * @return boolean
     */
    public function validateHandle($pattern)
    {
        if (is_string($pattern) && strpos($pattern, 'routeller=') === 0)
            return true;

        return false;
    }

    /**
     * Resolve into Closure or callable
     * @return callable
     */
    public function resolveHandle($pattern)
    {
        list($class, $method) = explode('@', str_replace('routeller=', '', $pattern));

        /** @var Controller $controller */
        $controller = $class::instance();

        return array($controller, $method);
    }
}