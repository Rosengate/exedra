<?php
namespace Exedra\Routeller;

use Exedra\Routeller\ParserTypes\DynamicType;
use Minime\Annotations\Parser;

class AnnotationsParser extends Parser
{
    public function parseValue($value, $key = null)
    {
        $value = trim($value);
        $type = DynamicType::class;
        if (preg_match($this->typesPattern, $value, $found)) { // strong typed
            $type = $found[1];
            $value = trim(substr($value, strlen($type)));
            if (in_array($type, $this->types)) {
                $type = array_search($type, $this->types);
            }
        }

        return (new $type)->parse($value, $key);
    }
}