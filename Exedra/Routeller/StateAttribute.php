<?php

namespace Exedra\Routeller;

abstract class StateAttribute implements \Exedra\Routeller\Contracts\StateAttribute
{
    public function key()
    {
        return static::class;
    }
}