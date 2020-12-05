<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Path implements RouteAttribute
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getProperty(): string
    {
        return 'path';
    }

    public function getValue(): mixed
    {
        return $this->path;
    }
}