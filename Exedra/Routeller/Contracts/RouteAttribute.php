<?php

namespace Exedra\Routeller\Contracts;

#[\Attribute]
interface RouteAttribute
{
    public function getProperty() : string;

    public function getValue() : mixed;
}