<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Middleware implements RouteAttribute
{
    private string $middleware;

    public function __construct(string $middleware, array $params = [])
    {
        $this->middleware = $middleware;
    }

    public function getProperty(): string
    {
        return 'middleware';
    }

    public function getValue(): string
    {
        return $this->middleware;
    }
}