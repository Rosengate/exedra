<?php
namespace Exedra\Functions;

abstract class Arrays
{
	public static function initiateByNotation(&$storage, $data, $notation = '.')
	{
		foreach($data as $key => $value)
		{
			unset($storage[$key]);
			$next = &self::setByNotation($storage, $key, $value, $notation);

			// recursive.
			if(is_array($value))
				self::initiateByNotation($next, $value, $notation);
		}
	}

	public static function &setByNotation(&$storage, $key, $value, $notation = '.')
	{
		// temporary replacement for escaped dot.
		$key	= str_replace("\.", "z#G1", $key);
		$keys	= explode($notation,$key);
		foreach($keys as $key)
		{
			$key	= str_replace("z#G1",".",$key);
			if(!isset($storage[$key]) || !is_array($storage[$key]))
				$storage[$key]	= Array();

			$storage = &$storage[$key];
			$laststorage = &$storage;
		}

		$storage	= $value;

		return $laststorage;
	}

	public static function getByNotation($myarray, $key, $notation = '.')
	{
		$keys	= explode($notation,$key);

		foreach($keys as $key)
		{
			$myarray	= &$myarray[$key];
		}

		return $myarray;
	}

	public static function &getReferenceByNotation(&$storage, $key, $notation = '.')
	{
		$keys = explode($notation, $key);

		foreach($keys as $key)
			$storage = &$storage[$key];

		return $storage;
	}

	public static function hasByNotation($storage,$key,$notation = '.')
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

	public static function deleteByNotation(&$storage,$key,$notation = '.')
	{
		if($key == null)
		{
			$storage = Array();
			return;
		}

		$keys	= explode($notation,$key);

		foreach($keys as $no=>$key)
		{
			if($no == 0) continue;
			$key	= array_shift($keys);
			$storage =& $storage[$key];
		}

		unset($storage[array_shift($keys)]);
	}
}


?>