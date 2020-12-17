<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Series implements RouteAttribute
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getProperty(): string
    {
        return 'serieses';
    }

    public function getValue(): mixed
    {
        return [$this->key, $this->value];
    }
}