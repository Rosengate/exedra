<?php

namespace Exedra\Routeller\PropertyResolvers;

use Exedra\Config;
use Exedra\Routeller\Contracts\PropertyResolver;

class ConfigResolver implements PropertyResolver
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function resolve($key, $value)
    {
        if (is_string($value) && strpos($value, '$config.') === 0)
            return $this->config->get(trim($value, '$config.'));

        return $value;
    }
}