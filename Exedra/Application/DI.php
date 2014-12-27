<?php
namespace Exedra\Application;

class Di
{
	public function __construct($registries, $instance = null)
	{
		$this->register($registries);
		$this->instance = $instance;
	}

	/**
	 * Register dependency
	 * @param mixed key
	 * @param mixed val
	 */
	public function register($key, $val = null)
	{
		if(is_array($key)) foreach($key as $k=>$v) $this->$k = $v; else $this->$key = $val;
	}

	/**
	 * Check if has dependency used.
	 * @param string property
	 * @return boolean
	 */
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
			else if(!isset($class[1]))
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
			$this->save($property, $val);

			return true;
		}

		return false;
	}

	/**
	 * Save the dependency
	 * @param string property
	 * @param mixed val
	 */
	private function save($property, $val)
	{
		$this->$property = $val;
	}

	/**
	 * Return the dependency
	 * @param string property
	 * @return mixed
	 */
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