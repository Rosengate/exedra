<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::TARGET_CLASS)]
class Decorator implements RouteAttribute
{
    protected $decorator;

    public function __construct($decorator)
    {
        $this->decorator = $decorator;
    }

    public function getProperty(): string
    {
        return 'decorator';
    }

    public function getValue(): mixed
    {
        return $this->decorator;
    }
}