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
	 * registry exist check
	 * @param string type
	 * @return bool
	 */
	public function offsetExists($type)
	{
		return isset($this->dependencies[$type]);
	}

	/**
	 * Get container registry
	 * @param string key
	 * @return \Exedra\Container\Registry
	 */
	public function offsetGet($key)
	{
		return $this->dependencies[$key];
	}

	/**
	 * Register list of type dependencies
	 * Will definitely dismiss the previous registry
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
	public function registry($type)
	{
		return $this->dependencies[$type];
	}

	/**
	 * Invoke the service and save
	 * @param string name
	 * @return mixed
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
	 * Resolve and set the service as public property.
	 * @return mixed
	 */
	public function get($name)
	{
		if(isset($this->$name))
			return $this->$name;

		return $this->$name = $this->solve('services', $name);
	}

	/**
	 * All-capable dependency call.
	 * @param string type
	 * @param string name
	 * @param array args
	 * @return mixed
	 */
	public function dependencyCall($type, $name, array $args = array())
	{
		switch($type)
		{
			case 'services':
				return $this->$name;
			break;
			case 'callables':
				return $this->__call($name, $args);
			break;
			case 'factories':
				return $this->create($name, $args);
			break;
		}
	}

	/**
	 * Solve the given type of registry
	 * @param string type services|callables|factories
	 * @param string name
	 * @return mixed
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException for failing to find in registry
	 */
	protected function solve($type, $name, array $args = array())
	{
		if(!$this->dependencies[$type]->has($name))
			throw new \Exedra\Exception\InvalidArgumentException('Unable to find the ['.$name.'] in the registered '.$type);

		$registry = $this->dependencies[$type]->get($name);

		return $this->resolve($registry, $args);
	}

	/**
	 * Actual resolve the given type of registry
	 * @param mixed registry
	 * @return mixed
	 */
	protected function resolve($registry, array $args = array())
	{
		if($registry instanceof \Closure)
			return call_user_func_array($registry->bindTo($this), $args);

		if(is_string($registry))
		{
			if(count($args) == 0)
				return new $registry();

			$reflection = new \ReflectionClass($registry);

			return $reflection->newInstanceArgs($args);
		}

		if(is_array($registry))
		{
			$class=  $registry[0];

			// only fully qualified class name passed
			if(!isset($registry[1]))
			{
				return new $class;
			}
			// has argument passed
			else
			{
				$reflection = new \ReflectionClass($registry[0]);

				$arguments = array();

				// the second element isn't an array
				if(!is_array($registry[1]))
					throw new \Exedra\Exception\InvalidArgumentException('Second element for array based registry must be an array');

				foreach($registry[1] as $arg)
				{
					// if isn't string. allow only string.
					if(!is_string($arg))
						throw new \Exedra\Exception\InvalidArgumentException('argument must be string');

					switch($arg)
					{
						case 'self':
							$arguments[] = $this;
						break;
						default:
							$split = explode('.', $arg, 2);

							if(isset($split[1]))
							{
								switch($split[0])
								{
									case 'self':
										$arguments[] = $this->get($split[1]);
									break;
									case 'services':
										$arguments[] = $this->get($split[1]);
									break;
									case 'factories':
										$arguments[] = $this->create($split[1]);
									break;
									case 'callables':
										$arguments[] = $this->__call($split[1]);
									break;
									default:
										$arguments[] = $this->get($arg);
									break;
								}
							}
							else
							{
								$arguments[] = $this->$arg;
							}
						break;
					}
				}

				// merge with the one passed
				$arguments = array_merge($arguments, $args);

				return $reflection->newInstanceArgs($arguments);
			}
		}

		throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve the dependency');
	}
}


?>