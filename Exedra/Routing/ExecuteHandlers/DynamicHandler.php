<?php
namespace Exedra\Routing\ExecuteHandlers;

use Exedra\Contracts\Routing\ExecuteHandler;

class DynamicHandler implements ExecuteHandler
{
    protected $validation;

    protected $resolve;

    public function validateHandle($pattern)
    {
        $validation = $this->validation;

        return $validation($pattern);
    }

    public function resolveHandle($pattern)
    {
        $resolve = $this->resolve;

        return $resolve($pattern);
    }

    public function onValidate(\Closure $validation)
    {
        $this->validation = $validation;

        return $this;
    }

    public function onResolve(\Closure $resolve)
    {
        $this->resolve = $resolve;

        return $this;
    }
}