<?php

namespace Exedra\Support\Wireman\Exceptions;

use Exedra\Exception\Exception;

class ParamResolveException extends Exception
{
    protected $parameter;

    public function __construct(\ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * @return \ReflectionParameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}