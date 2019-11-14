<?php

namespace Exedra\Routeller\Contracts;

interface RoutePropertiesReader
{
    public function readProperties(\Reflector $reflector);
}