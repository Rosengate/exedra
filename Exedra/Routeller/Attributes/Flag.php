<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Flag implements RouteAttribute
{
    protected $flag;

    public function __construct($flag)
    {
        $this->flag = $flag;
    }

    public function getProperty(): string
    {
        return 'flags';
    }

    public function getValue(): mixed
    {
        return [$this->flag];
    }
}