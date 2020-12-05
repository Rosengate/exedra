<?php

namespace Exedra\Routeller;

use Exedra\Routeller\Attributes\Attr;
use Exedra\Routeller\Attributes\Config;
use Exedra\Routeller\Attributes\Middleware;
use Exedra\Routeller\Attributes\State;
use Exedra\Routeller\Contracts\RouteAttribute;
use Exedra\Routeller\Contracts\StateAttributeHandler;
use Exedra\Routeller\Contracts\RoutePropertiesReader;
use Exedra\Support\DotArray;

class AttributesReader implements RoutePropertiesReader
{
    /**
     * @var StateAttributeHandler[]
     */
    private array $handlers;

    /**
     * AttributesReader constructor.
     * @param StateAttributeHandler[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * @param \Reflector|\ReflectionMethod $reflector
     */
    public function readProperties(\Reflector $reflector)
    {
        $attributes = $reflector->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        $properties = [];

        $middlewares = [];

        foreach ($attributes as $reflection) {
            /** @var RouteAttribute $attr */
            $attr = $reflection->newInstance();

            $property = $attr->getProperty();

            if ($attr instanceof Middleware)
                $middlewares[] = $attr->getValue();
            else if (isset($properties[$property]) && ($attr instanceof State || $attr instanceof Attr || $attr instanceof Config))
                $properties[$property] = array_merge($properties[$property], $attr->getValue());
            else
                DotArray::set($properties, $attr->getProperty(), $attr->getValue());
        }

        if (count($middlewares) > 0)
            DotArray::set($properties, 'middleware', $middlewares);

        foreach ($this->handlers as $handler) {
            foreach ($reflector->getAttributes($handler->name(), \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $state = $handler->handle($attribute);

                DotArray::set($properties, 'state', [
                    $state->key => $state->value
                ]);
            }
        }

        return $properties;
    }
}