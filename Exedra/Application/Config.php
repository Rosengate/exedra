<?php namespace Exedra\Application;

class Config
{
	private $storage = array();

	/**
	 * Set config value
	 * @param mixed key or array
	 * @param mixed value
	 * @return this
	 */
	public function set($key,$value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
				$this->set($k, $v);

			return $this;
		}

		\Exedra\Functions\Arrays::setByNotation($this->storage,$key,$value);
		return $this;
	}

	/**
	 * Get config value
	 * @param key
	 * @return value
	 */
	public function get($key)
	{
		return \Exedra\Functions\Arrays::getByNotation($this->storage,$key);
	}

	/**
	 * Check key existence.
	 * @param string key.
	 */
	public function has($key)
	{
		return \Exedra\Functions\Arrays::hasByNotation($this->storage,$key);
	}
}