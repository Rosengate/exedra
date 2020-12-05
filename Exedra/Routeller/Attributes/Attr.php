<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;
use JetBrains\PhpStorm\Deprecated;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
/**
 * @deprecated
 */
class Attr implements RouteAttribute
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
        return 'attr';
    }

    public function getValue(): array
    {
        return [
            $this->key => $this->value
        ];
    }
}