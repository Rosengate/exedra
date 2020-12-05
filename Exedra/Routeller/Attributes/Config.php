<?php

namespace Exedra\Routeller\Attributes;

use Exedra\Routeller\Contracts\RouteAttribute;
use Exedra\Support\DotArray;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Config implements RouteAttribute
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

//    public static function getKeyValues(array $arguments)
//    {
//        $config = [];
//
//        DotArray::set($config, $arguments[0], $arguments[1]);
//
//        return ['config', $config];
//    }

    public function getProperty(): string
    {
        return 'config';
    }

    public function getValue(): array
    {
        $config = [];

        DotArray::set($config, $this->key, $this->value);

        return $config;
    }
}