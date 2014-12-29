<?php namespace Exedra\Application;

class Config
{
	private $storage = array();

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

	public function get($key)
	{
		return \Exedra\Functions\Arrays::getByNotation($this->storage,$key);
	}

	public function has($key)
	{
		return \Exedra\Functions\Arrays::hasByNotation($this->storage,$key);
	}
}