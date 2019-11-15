<?php

namespace Exedra\Routeller\Contracts;

/**
 * A runtime properties resolver
 *
 * Interface PropertiesResolver
 * @package Exedra\Routeller\Contracts
 */
interface PropertyResolver
{
    public function resolve($key, $value);
}
