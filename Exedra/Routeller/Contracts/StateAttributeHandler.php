<?php

namespace Exedra\Routeller\Contracts;

use Exedra\Routeller\StateAttributeValue;

interface StateAttributeHandler
{
    /**
     * Attribute class name
     * @return string
     */
    public function name() : string;

    /**
     * Transform into route state object
     * @param \ReflectionAttribute $attribute
     * @return StateAttributeValue
     */
    public function handle(\ReflectionAttribute $attribute) : StateAttributeValue;
}