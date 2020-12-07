<?php

namespace Exedra\Routeller;

use Exedra\Routeller\Attributes\Attr;
use Exedra\Routeller\Attributes\Config;
use Exedra\Routeller\Attributes\Flag;
use Exedra\Routeller\Attributes\Middleware;
use Exedra\Routeller\Attributes\State;
use Exedra\Routeller\Contracts\RouteAttribute;
use Exedra\Routeller\Contracts\StateAttribute;
use Exedra\Routeller\Contracts\StateAttributeHandler;
use Exedra\Routeller\Contracts\RoutePropertiesReader;
use Exedra\Support\DotArray;

class AttributesReader implements RoutePropertiesReader
{
    /**
     * @param \Reflector|\ReflectionMethod $reflector
     */
    public function readProperties(\Reflector $reflector)
    {
        $properties = [];

        $middlewares = [];

        foreach ($reflector->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $reflection) {
            /** @var RouteAttribute $attr */
            $attr = $reflection->newInstance();

            $property = $attr->getProperty();

            if ($attr instanceof Middleware)
                $middlewares[] = $attr->getValue();
            else if (isset($properties[$property]) && ($attr instanceof State || $attr instanceof Attr || $attr instanceof Config || $attr instanceof Flag))
                $properties[$property] = array_merge($properties[$property], $attr->getValue());
            else
                DotArray::set($properties, $attr->getProperty(), $attr->getValue());
        }

        if (count($middlewares) > 0)
            DotArray::set($properties, 'middleware', $middlewares);

        /** @var \ReflectionAttribute $attribute */
        foreach ($reflector->getAttributes(StateAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            /** @var StateAttribute $stateAttribute */
            $stateAttribute = $attribute->newInstance();

            if (!isset($properties['states']))
                $properties['states'] = [];

            DotArray::set($properties['states'], $stateAttribute->key(), $stateAttribute->value());

//            $properties['state'][$stateAttribute->key()] = $stateAttribute->value();

//            DotArray::set($properties, 'state', [
//                $stateAttribute->key() => $stateAttribute->value()
//            ]);
        }

        echo '<pre>';
        print_r($properties);

//        foreach ($this->handlers as $handler) {
//            foreach ($reflector->getAttributes($handler->name(), \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
//                $state = $handler->handle($attribute);
//
//                DotArray::set($properties, 'state', [
//                    $state->key => $state->value
//                ]);
//            }
//        }

        return $properties;
    }
}