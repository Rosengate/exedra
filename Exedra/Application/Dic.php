<?php namespace Exedra\Application;

class Di
{
	protected $storage;
	protected $registry = array();
	protected $dependency = array();
	public $save = true;

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

		return $this;
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
	public function __call($dependency, $args = array())
	{
		if(isset($this->registry[$dependency]))
		{
			return $this->get($dependency, $args);
		}
	}

	/**
	 * Resolve the dependency
	 * @param string property
	 * @return mixed
	 */
	public function get($dependency, $args = false)
	{
		if(isset($this->storage[$dependency]))
			return $this->storage[$dependency];

		$class	= $this->registry[$dependency];

		if($class instanceof \Closure)
		{
			$val	= is_array($args) ? call_user_func_array($class, $args) : $class();
		}
		else if(is_object($class))
		{
			$val	= $class;
		}
		else if(!isset($class[1]))
		{
			if(is_array($args) && count($args) > 0)
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
			if(is_array($args))
				$classArgs = array_merge($class[1], $args);
			else
				$classArgs = $class[1];

			$reflection	= new \ReflectionClass($class[0]);
			$obj	= $reflection->newInstanceArgs($classArgs);

			$val	= $obj;
		}

		// only save if the flag is true, and no constructer was passed. (args === false)
		if($this->save && $args === false)
			$this->storage[$dependency] = $val;

		return $this->storage[$dependency];

	}
}


?>