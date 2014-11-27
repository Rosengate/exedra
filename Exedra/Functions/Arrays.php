<?php
namespace Exedra\Functions;

abstract class Arrays
{
	public static function setByNotation(&$storage,$key,$value,$notation = ".")
	{
		## temporary replacement for escaped dot.
		$key	= str_replace("\.", "z#G1", $key);
		$keys	= explode($notation,$key);
		foreach($keys as $key)
		{
			$key	= str_replace("z#G1",".",$key);
			if(!isset($storage[$key]) || !is_array($storage[$key]))
				$storage[$key]	= Array();

			$storage = &$storage[$key];
		}

		$storage	= $value;
	}

	public static function getByNotation($myarray,$key,$notation = ".")
	{
		$keys	= explode($notation,$key);

		foreach($keys as $key)
		{
			$myarray	= &$myarray[$key];
		}

		return $myarray;
	}

	public static function hasByNotation($storage,$key,$notation = ".")
	{
		$keys	= explode($notation,$key);

		foreach($keys as $key)
		{
			if(!isset($storage[$key]))
			{
				return false;
			}

			$storage	= $storage[$key];
		}

		return true;
	}
}


?>