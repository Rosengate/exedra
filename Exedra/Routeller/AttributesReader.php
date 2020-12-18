<?php

namespace Exedra\Routeller;

use Exedra\Routeller\Attributes\Attr;
use Exedra\Routeller\Attributes\Config;
use Exedra\Routeller\Attributes\Flag;
use Exedra\Routeller\Attributes\Middleware;
use Exedra\Routeller\Attributes\Series;
use Exedra\Routeller\Attributes\State;
use Exedra\Routeller\Contracts\RouteAttribute;
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

            $value = $attr->getValue();

            if ($attr instanceof Middleware) {
                $middlewares[] = $attr->getValue();
            } else if ($attr instanceof Series) {
                if (!isset($properties[$property]))
                    $properties[$property] = [];

                if (!isset($properties[$property][$value[0]]))
                    $properties[$property][$value[0]] = [];

                $properties[$property][$value[0]][] = $value[1];
            } else if (isset($properties[$property]) && ($attr instanceof State || $attr instanceof Attr || $attr instanceof Config || $attr instanceof Flag)) {
                $properties[$property] = array_merge($properties[$property], $attr->getValue());
            } else {
                DotArray::set($properties, $attr->getProperty(), $attr->getValue());
            }
        }

        if (count($middlewares) > 0)
            DotArray::set($properties, 'middleware', $middlewares);

        return $properties;
    }
}