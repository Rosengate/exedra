<?php
namespace Exedra\Wizard;

class Arguments implements \ArrayAccess
{
	protected $data = array();

	public function __construct(array $data = array())
	{
		$this->data = $data;
	}

	public function offsetGet($name)
	{
		return $this->get($name);
	}

	public function offsetSet($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function offsetExists($name)
	{
		foreach($this->data as $key => $value)
		{
			if(strpos($name, $key) === 0)
				return true;
		}

		return false;
	}

	public function offsetUnset($name)
	{
		unset($this->data[$name]);
	}

	public function get($name, $default = null)
	{
		$values = array();

		foreach($this->data as $key => $value)
		{
			if(strpos($name, $key) === 0)
				return $value;
		}

		return $default;
	}

	public function has($name)
	{
		foreach($this->data as $key => $value)
		{
			if(strpos($name, $key) === 0)
				return true;
		}

		return false;
	}
}