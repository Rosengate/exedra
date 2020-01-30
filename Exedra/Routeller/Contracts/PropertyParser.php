<?php

namespace Exedra\Routeller\Contracts;

interface PropertyParser
{
    public function keyTo(\Reflector $reflector, $key);

    public function valueTo(\Reflector $reflector, $value);
}