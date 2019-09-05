<?php

namespace Foo\Ctrls;

use Exedra\Routeller\Controller\Controller;
use Exedra\Runtime\Context;

/**
 * @path /deferred
 * @name deferred-test
 */
class DeferredTestController extends Controller
{
    public function middleware(Context $context)
    {
        return $context->next($context);
    }

    /**
     * @path /bar
     */
    public function get()
    {
    }
}