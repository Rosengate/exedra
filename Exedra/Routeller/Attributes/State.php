<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class State implements RouteAttribute
{
    const IS_REPEATABLE = true;
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getProperty(): string
    {
        return 'state';
    }

    public function getValue(): mixed
    {
        return [
            $this->key => $this->value
        ];
    }
}