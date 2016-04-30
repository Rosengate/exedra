<?php namespace Exedra\Container;

class Container implements \ArrayAccess
{
	/**
	 * Container dependencies registry
	 * @var array of services, callables, and factories
	 */
	protected $dependencies = array();

	public function __construct()
	{
		$this->dependencies = array(
			'services' => new \Exedra\Container\Registry,
			'callables' => new \Exedra\Container\Registry,
			'factories' => new \Exedra\Container\Registry,
		);
	}

	/**
	 * Dependency registry exist check
	 * @param string type
	 * @return bool
	 */
	public function offsetExists($type)
	{
		return isset($this->dependencies[$type]);
	}

	/**
	 * Get referenced dependency registry
	 * @param string key
	 * @return \Exedra\Container\Registry
	 */
	public function offsetGet($key)
	{
		return $this->dependencies[$key];
	}

	/**
	 * Register list of type dependencies
	 * Will definitely dismiss the previous registered dependency
	 * @param string service|callable|factory
	 * @param array registry
	 */
	public function offsetSet($type, $registry)
	{
		return $this->dependencies[$type] = $registry;
	}

	/**
	 * Empty the dependencies type
	 * @param string key
	 */
	public function offsetUnset($key)
	{
		$this->dependencies[$key]->clear();
	}

	/**
	 * Get container registry
	 * @param string type
	 * @return \Exedra\Container\Registry
	 */
	public function getRegistry($type)
	{
		return $this->dependencies[$type];
	}

	/**
	 * Invoke the service and save
	 * @param string dependency
	 * @return this->get(dependency)
	 */
	public function __get($name)
	{
		return $this->$name = $this->solve('services', $name);
	}

	/**
	 * Invoke the registered callable
	 * @param string name
	 * @param array args
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __call($name, array $args = array())
	{
		return $this->solve('callables', $name, $args);
	}

	/**
	 * Invoke the registered factory
	 * @param string name
	 * @param array args
	 * @return mixed
	 * 
	 * @throws \InvalidArgumentException
	 */
	public function create($name, array $args = array())
	{
		return $this->solve('factories', $name, $args);
	}

	/**
	 * Alias to create()
	 * @param string name
	 * @param array args
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function make($name, array $args = array())
	{
		return $this->create($name, $args);
	}

	/**
	 * Resolve and set the service
	 * @return mixed
	 */
	public function get($name)
	{
		if(isset($this->$name))
			return $this->$name;

		return $this->$name = $this->solve('services', $name);
	}

	/**
	 * Solve the given type of dependency
	 * @param string type services|callables|factories
	 * @param string name dependency name
	 * @return mixed
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException for failing to find in registry
	 */
	protected function solve($type, $name, array $args = array())
	{
		if(!$this->dependencies[$type]->has($name))
			throw new \Exedra\Exception\InvalidArgumentException('Unable to find the ['.$name.'] in the registered dependecy '.$type);

		$registry = $this->dependencies[$type]->get($name);

		return $this->resolve($registry, $args);
	}

	/**
	 * Actual resolve the given type of dependency
	 * @param mixed registry
	 * @return mixed
	 */
	protected function resolve($registry, array $args = array())
	{
		if($registry instanceof \Closure)
			return call_user_func_array($registry->bindTo($this), $args);

		if(is_callable($registry))
			return call_user_func_array($registry, $args);

		if(is_object($registry))
			return $registry;

		if(is_array($registry))
		{
			if(!isset($registry[1]))
			{
				return new $registry[0];
			}
			else
			{
				$reflection = new \ReflectionClass($registry[0]);

				return $reflection->newInstanceArgs($registry[1]);
			}
		}

		throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve the dependency');
	}
}


?>