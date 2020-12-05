<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Tag implements RouteAttribute
{
    private $tag;

    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }

    public function getProperty(): string
    {
        return 'tag';
    }

    public function getValue(): mixed
    {
        return $this->tag;
    }
}