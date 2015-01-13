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
	 * Magically alias to get.
	 * @param string dependency
	 * @return this->get(dependency)
	 */
	public function __get($dependency)
	{
		return $this->get($dependency);
	}

	/**
	 * Magically you can pass additional argument you want to the dependency constructor.
	 */
	public function __call($dependency, $args)
	{
		if(isset($this->registry[$dependency]))
			return $this->get($dependency, $args);
	}

	/**
	 * Resolve the dependency
	 * @param string property
	 * @return mixed
	 */
	public function get($dependency, $args = array())
	{
		if(isset($this->storage[$dependency]))
			return $this->storage[$dependency];

		$class	= $this->registry[$dependency];

		if($class instanceof \Closure)
		{
			$val	= call_user_func_array($class, $args);
		}
		else if(is_object($class))
		{
			$val	= $class;
		}
		else if(!isset($class[1]))
		{
			if(count($args) > 0)
			{
				$reflection	= new \ReflectionClass($class[0]);
				$obj	= $reflection->newInstanceArgs($args);

				$val	= $obj;
			}
			else
			{
				$val = new $class[0];
			}
		}
		else
		{
			// merge with passed argument if has any.
			$args = array_merge($class[1], $args);
			$reflection	= new \ReflectionClass($class[0]);
			$obj	= $reflection->newInstanceArgs($args);

			$val	= $obj;
		}

		$this->storage[$dependency] = $val;

		return $this->storage[$dependency];

	}
}


?>