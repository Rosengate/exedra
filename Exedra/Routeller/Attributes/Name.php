<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Name implements RouteAttribute
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getProperty(): string
    {
        return 'name';
    }

    public function getValue(): mixed
    {
        return $this->name;
    }
}