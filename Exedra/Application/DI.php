<?php
namespace Exedra\Application;

class Di
{
	public function __construct($registries, $instance = null)
	{
		$this->register($registries);
		$this->instance = $instance;
	}

	public function register($key, $val = null)
	{
		if(is_array($key)) foreach($key as $k=>$v) $this->$k = $v; else $this->$key = $val;
	}

	public function has($property)
	{
		if(isset($this->$property))
		{
			$class	= $this->$property;

			if($class instanceof \Closure)
			{
				$val	= $class();
			}
			else if(is_object($class))
			{
				$val	= $class;
			}
			else if(!$class[1])
			{
				$val	= new $class[0];
			}
			else
			{
				$reflection	= new \ReflectionClass($class[0]);
				$obj	= $reflection->newInstanceArgs($class[1]);

				$val	= $obj;
			}

			## register as property.
			$this->set($property, $val);

			return true;
		}
	}

	private function set($property, $val)
	{
		$this->$property = $val;
	}

	public function get($property)
	{
		if($this->has($property))
		{
			## register and return.
			$this->instance->$property = $this->$property;
			return $this->instance->$property;
		}

		return null;
	}
}


?>