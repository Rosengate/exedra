<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Method implements RouteAttribute
{
    private array|string $method;

    public function __construct(string|array $method)
    {
        $this->method = $method;
    }

    public function getProperty(): string
    {
        return 'method';
    }

    public function getValue(): array|string
    {
        return $this->method;
    }
}