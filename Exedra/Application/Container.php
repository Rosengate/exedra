<?php namespace Exedra\Application;

class Container implements \ArrayAccess
{
	/**
	 * Container registries
	 * @var array of services, callable
	 */
	protected $dependencies = array(
		'services' => array(),
		'callable' => array()
		);

	/**
	 * Flag whether to save the dependency or not.
	 * @var boolean
	 */
	public $_save = true;

	public function __construct($registries = null)
	{
		if($registries)
			$this->register($registries);
	}

	public function offsetExists($type)
	{
		return isset($this->dependencies[$type]);
	}

	public function &offsetGet($key)
	{
		return $this->dependencies[$key];
	}

	public function offsetSet($type, $registry)
	{
		return $this->dependencies[$type] = $registry;
	}

	public function offsetUnset($key)
	{
		unset($this->dependencies[$key]);
	}

	/**
	 * Register dependency
	 * @param mixed key
	 * @param mixed val
	 */
	public function register($key, $val = null)
	{
		if(is_array($key))
			foreach($key as $k=>$v)
				$this->register($k, $v);
		else
			$this->dependencies['services'][$key] = $val;

		return $this;
	}

	/**
	 * Getter for services
	 * @param string dependency
	 * @return this->get(dependency)
	 */
	public function __get($dependency)
	{
		return $this->get($dependency);
	}

	/**
	 * Invoke the registried callable
	 */
	public function __call($name, $args = array())
	{
		if(!isset($this->dependencies['callable'][$name]))
			throw new \Exception('Unable to find the callable registry for \''.$name.'\'');

		$registry = $this->dependencies['callable'][$name];

		if($registry instanceof \Closure)
			return call_user_func_array($registry->bindTo($this), $args);

		if(is_callable($registry))
			return call_user_func_array($registry, $args);

		throw new \Exception('Registry '.$name.' must be an instance of \Closure');
	}

	/**
	 * Resolve and set the service
	 * @return mixed
	 */
	public function get($name)
	{
		if(!isset($this->dependencies['services'][$name]))
			return null;

		$registry	= $this->dependencies['services'][$name];

		if($registry instanceof \Closure)
		{
			$registry = $registry->bindTo($this);

			$service = $registry();
		}
		else if(is_object($registry))
		{
			$service	= $registry;
		}
		else if(is_array($registry))
		{
			if(!isset($registry[1]))
			{
				$service = new $registry[0];
			}
			else
			{
				$reflection = new \ReflectionClass($registry[0]);

				$service = $reflection->newInstanceArgs($registry[1]);
			}
		}

		$this->$name = $service;

		return $service;
	}
}


?>