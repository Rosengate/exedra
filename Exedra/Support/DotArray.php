<?php
namespace Exedra\Support;

class DotArray
{
	/**
	 * Initialize array
	 * @param array &storage
	 * @param array data
	 */
	public static function initialize(&$storage, array $data)
	{
		foreach($data as $key => $value)
		{
			unset($storage[$key]);

			$next = &self::set($storage, $key, $value);

			// recursive.
			if(is_array($value))
				self::initialize($next, $value);
		}
	}

	/**
	 * Set value
	 * @param array &storage
	 * @param string key
	 * @param mxied value
	 */
	public static function &set(&$storage, $key, $value)
	{
		// temporary replacement for escaped dot.
		$key = str_replace("\.", "z#G1", $key);

		$keys = explode('.', $key);

		foreach($keys as $key)
		{
			$key = str_replace("z#G1", ".", $key);

			if(!isset($storage[$key]) || !is_array($storage[$key]))
				$storage[$key]	= array();

			$storage = &$storage[$key];
			
			$laststorage = &$storage;
		}

		$storage = $value;

		return $laststorage;
	}

	/**
	 * Get value
	 * @param array storage
	 * @param string key
	 * @return mixed
	 */
	public static function get($storage, $key)
	{
		$keys	= explode('.', $key);

		foreach($keys as $key)
			$storage	= &$storage[$key];

		return $storage;
	}

	/**
	 * Get referenced value
	 * @param array storage
	 * @param string key
	 * @return &mixed
	 */
	public static function &getReference(&$storage, $key)
	{
		$keys = explode('.', $key);

		foreach($keys as $key)
			$storage = &$storage[$key];

		return $storage;
	}

	/**
	 * Recursively loop through multidimensional array
	 * With every loop receive a dotted key
	 * @param &array storage
	 * @param \Closure callback
	 * @param array prefix
	 */
	public static function each(&$storage, \Closure $callback, array $prefix = array())
	{
		foreach($storage as $key => &$value)
		{
			if(is_array($value))
				static::each($value, $callback, array_merge($prefix, array($key)));
			else
				$callback(implode('.', array_merge($prefix, array($key))), $value, $storage[$key]);
		}
	}

	/**
	 * Check key existence
	 * @param array storage
	 * @param string key
	 * @return bool
	 */
	public static function has($storage, $key)
	{
		$keys	= explode('.', $key);

		foreach($keys as $key)
		{
			if(!isset($storage[$key]))
				return false;

			$storage	= $storage[$key];
		}

		return true;
	}

	/**
	 * Delete key
	 * @param array &storage
	 * @param string key
	 */
	public static function delete(&$storage, $key)
	{
		if($key == null)
		{
			$storage = array();
			return;
		}

		$keys	= explode('.', $key);

		foreach($keys as $no=>$key)
		{
			if($no == 0) continue;

			$key	= array_shift($keys);
			
			$storage =& $storage[$key];
		}

		unset($storage[array_shift($keys)]);
	}
}