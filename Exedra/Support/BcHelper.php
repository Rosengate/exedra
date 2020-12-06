<?php

namespace Exedra\Support;

use Exedra\Exception\Exception;

class BcHelper
{
    public static function ReflectionParamIsClass(\ReflectionParameter $param)
    {
        $version = phpversion();

        if ((version_compare($version, '7.0.0', '>='))) {
            $type = $param->getType();

            if (!$type)
                return false;

            if ((version_compare($version, '8.0.0', '>=')) && $type instanceof \ReflectionUnionType)
                throw new Exception('Unsupported union type');

            if (in_array((string) $type, ['array', 'string', 'int', 'float', 'bool']))
                return false;

            return true;
        } else {
            return !!$param->getClass();
        }
    }

    public static function ReflectionParamGetClass(\ReflectionParameter $param)
    {
        $version = phpversion();

        if ((version_compare($version, '7.0.0', '>='))) {
            $type = $param->getType();
//            if (!$type)
//                throw new Exception('Not a class');
//
//            if ((version_compare($version, '8.0.0', '>=')) && $type instanceof \ReflectionUnionType)
//                throw new Exception('Unsupported union type');
//
//            if (in_array((string) $type, ['array', 'string', 'int', 'float', 'bool']))
//                throw new Exception('Not a class');

            return (string) $type;
        } else {
            return $param->getClass()->getName();
        }
    }
}