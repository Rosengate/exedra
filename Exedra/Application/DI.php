<?php
namespace Exedra\Application;

class Di
{
	private $storage;
	private $registry = array();
	private $dependency = array();

	public function __construct($registries = null)
	{
		if($registries)
			$this->register($registries);
	}

	/**
	 * Register dependency
	 * @param mixed key
	 * @param mixed val
	 */
	public function register($key, $val = null)
	{
		if(is_array($key)) foreach($key as $k=>$v) $this->register($k, $v); else $this->registry[$key] = $val;
	}

	/**
	 * Check if the the registry has been registered.
	 * @param string
	 * @return boolean
	 */
	public function has($dependency)
	{
		return isset($this->registry[$dependency]);
	}

	/**
	 * Resolve the dependency
	 * @param string property
	 * @return mixed
	 */
	public function get($dependency)
	{
		if(isset($this->storage[$dependency]))
			return $this->storage[$dependency];

		$class	= $this->registry[$dependency];

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

		$this->storage[$dependency] = $val;

		return $this->storage[$dependency];

	}
}


?>