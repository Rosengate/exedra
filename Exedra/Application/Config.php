<?php namespace Exedra\Application;

class Config
{
	private $storage = array();

	public function set($key,$value)
	{
		\Exedra\Functions\Arrays::setByNotation($this->storage,$key,$value);
		return $this;
	}

	public function get($key)
	{
		return \Exedra\Functions\Arrays::getByNotation($this->storage,$key);
	}

	public function has($key)
	{
		return \Exedra\Functions\Arrays::hasByNotation($this->storage,$key);
	}
}