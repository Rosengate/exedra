<?php
namespace Exedra\Wizard;

class Arguments implements \ArrayAccess
{
	protected $data = array();

	public function __construct(array $base, array $arguments = array())
	{
		$this->data = $arguments;

		$unfounds = array();

		// validate against base arguments
		foreach($arguments as $arg => $value)
		{
			$found = false;

			foreach($base as $baseArg)
			{
				if(strpos($baseArg, $arg) === 0)
					$found = true;
			}

			if(!$found)
				$unfounds[] = $arg;
		}

		if(count($unfounds) > 0)
			throw new \Exedra\Exception\InvalidArgumentException('Unable to find argument(s) with name ['.implode(', ', $unfounds).']');
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