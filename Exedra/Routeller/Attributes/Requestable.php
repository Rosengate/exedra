<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Requestable implements RouteAttribute
{
    private bool $requestable;

    public function __construct($requestable = true)
    {
        $this->requestable = $requestable;
    }

    public function getProperty(): string
    {
        return 'requestable';
    }

    public function getValue(): mixed
    {
        return $this->requestable;
    }
}