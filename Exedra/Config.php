<?php
namespace Exedra;

use Exedra\Support\DotArray;

class Config implements \ArrayAccess
{
	/**
	 * Config storage
	 * @var array $storage
	 */
	protected $storage = array();

	/**
	 * Set config value
	 * @param string|array $key
	 * @param mixed $value
	 * @return $this
	 */
	public function set($key, $value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k => $v)
				$this->set($k, $v);

			return $this;
		}

		DotArray::set($this->storage, $key, $value);

		return $this;
	}

    /**
     * Get config value
     * @param string $key
     * @param string|null $default
     * @return mixed|null $value
     */
	public function get($key, $default = null)
	{
		if(!$this->has($key))
			return $default;

		return DotArray::get($this->storage, $key);
	}

	/**
	 * Get everything within storage.
	 * @return array
	 */
	public function getAll()
	{
		return $this->storage;
	}

	/**
	 * Check key existence.
	 * @param string $key
     * @return bool
	 */
	public function has($key)
	{
		return DotArray::has($this->storage, $key);
	}

	/**
	 * Set config through array offset
	 * @param string $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Get config through array offset
	 * @param string $key
	 * @return mixed
	 */
	public function &offsetGet($key)
	{
		if(!$this->has($key))
			return null;

		return DotArray::getReference($this->storage, $key);
	}

	/**
	 * Check config existence through array offset
	 * @param string $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Unset config through array offset
	 * @param string $key
	 */
	public function offsetUnset($key)
	{
		DotArray::delete($this->storage, $key);
	}
}