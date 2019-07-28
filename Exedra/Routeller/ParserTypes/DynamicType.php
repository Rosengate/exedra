<?php

namespace Exedra\Routeller\ParserTypes;

use Minime\Annotations\Interfaces\TypeInterface;

class DynamicType implements TypeInterface
{
    /**
     * Parses a type
     * @param  string $value value to be processed
     * @param  string $annotation annotation name
     * @return mixed
     */
    public function parse($value, $annotation = null)
    {
        if ('' === $value) return true; // implicit boolean

        $json = json_decode($value, true);

        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        } elseif (false !== ($int = filter_var($value, FILTER_VALIDATE_INT))) {
            return $int;
        } elseif (false !== ($float = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            return $float;
        }

        return $value;
    }
}