<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class AsFailRoute implements RouteAttribute
{
    private bool $bool;

    public function __construct($bool = true)
    {
        $this->bool = $bool;
    }

    public function getProperty(): string
    {
        return 'asFailRoute';
    }

    public function getValue(): mixed
    {
        return $this->bool;
    }
}