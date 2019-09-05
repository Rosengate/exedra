<?php

namespace Exedra\Routeller;

use Exedra\Support\DotArray;
use Minime\Annotations\Reader;

class AnnotationsReader extends Reader
{
    protected static $exceptions = array(
        'return' => 1,
        'param' => 1,
        'throws' => 1,
        'package' => 1
    );

    public function addExceptionTags(array $tags)
    {
        static::$exceptions = array_merge(static::$exceptions, array_flip($tags));
    }

    /**
     * @param \Reflector $Reflection
     * @return array
     */
    public function getRouteProperties(\Reflector $Reflection)
    {
        $doc = $Reflection->getDocComment();
        if ($this->cache) {
            $key = $this->cache->getKey($doc);
            $ast = $this->cache->get($key);
            if (!$ast) {
                $ast = $this->parser->parse($doc);
                $this->cache->set($key, $ast);
            }
        } else {
            $ast = $this->parser->parse($doc);
        }

        $properties = array();

        foreach ($ast as $key => $value) {
            if (isset(static::$exceptions[$key]))
                continue;

            if (strpos($key, 'attr.') === 0 && is_string($value) && strpos($value, '[] ') === 0) {
                $key .= '[]';
                $value = substr_replace($value, '', 0, strlen('[] '));
            }

            DotArray::set($properties, $key, $value);
        }

        return $properties;
    }
}