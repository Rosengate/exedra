<?php

namespace Exedra\Support\Wireman;

use Exedra\Routing\Call;
use Exedra\Runtime\Context;

class CallHandler implements \Exedra\Contracts\Runtime\CallHandler
{
    /**
     * @var Wireman
     */
    protected $wireman;

    /**
     * @var bool
     */
    protected $guarded;

    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context, Wireman $wireman, $guarded = false)
    {
        $this->wireman = $wireman;
        $this->guarded = $guarded;
        $this->context = $context;
    }

    public function handle(Call $call, array $args)
    {
        if (!$this->guarded || ($this->guarded && $this->context->attr('autowire')))
            $args = $this->wireman->resolveCallable($call->getCallable());

        return call_user_func_array($call, $args);
    }
}