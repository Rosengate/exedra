<?php

namespace Exedra\Routeller;

class StateAttributeValue
{
    public string $key;

    /**
     * @var mixed
     */
    public $value;

    public function __construct(string $key, mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}