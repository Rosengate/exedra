<?php
namespace Exedra\Routeller\Controller;

abstract class Controller
{
    protected static $instances = array();

    protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function instance()
    {
        $classname = static::class;

        if(!isset(static::$instances[$classname]))
            static::$instances[$classname] = new static();

        return static::$instances[$classname];
    }
}