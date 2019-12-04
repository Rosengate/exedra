<?php

namespace Exedra\Support\Wireman\Exceptions;

use Exedra\Exception\Exception;

class ParamResolveException extends Exception
{
    protected $parameter;

    public function __construct(\ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;

        $this->message = 'Unable to resolve param $' . $parameter->getName();
    }

    /**
     * @return \ReflectionParameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}