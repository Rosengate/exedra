<?php
namespace Exedra\Runtime;

/**
 * Handle list of registered execution handler
 */
class Handlers
{
	/**
	 * Application instance
	 * @var \Exedra\Application
	 */
	protected $app;

	/**
	 * Registry of execution bindable in application layer.
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * List of instantiated handlers
	 * @var array handlers
	 */
	protected $handlers = array();

	public function __construct()
	{
		$this->registerDefaultHandlers();
	}

	protected function registerDefaultHandlers()
	{
		$this->register('closure', '\Exedra\Runtime\Handler\Closure');
	}

	/**
	 * Register execution handler
	 * @param string name
	 * @param string class
	 * @return self
	 */
	public function register($name, $class)
	{
		$this->registry[$name] = $class;

		return $this;
	}

	/**
	 * Alias for register()
	 */
	public function add($name, $class)
	{
		return $this->register($name, $class);
	}

	/**
	 * Resolve a handler
	 * @param \Exedra\Runtime\Exe exe
	 * @param mixed pattern
	 * @return \Closure
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 * @throws \Exedra\Exception\NotFoundException
	 */
	public function resolve(\Exedra\Runtime\Exe $exe, $pattern, array $handlers = array())
	{
		foreach(array_merge($this->registry, $handlers) as $name => $className)
		{
			if(isset($this->handlers[$name]))
			{
				$handler = $this->handlers[$name];
			}
			else
			{
				if(is_string($className))
				{
					$handler = new $className($exe);
				}
				else if(is_object($className))
				{
					if($className instanceof \Closure)
					{
						$className($handler = new \Exedra\Runtime\Handler\Handler($exe));
					}
				}
			}

			if($handler->validate($pattern) === true)
			{
				$resolve = $handler->resolve($pattern);

				if(!(is_callable($resolve)))
					throw new \Exedra\Exception\InvalidArgumentException('The resolve() method for handler ['.get_class($handler).'] must return \Closure or callable');

				return $resolve;
			}
		}

		throw new \Exedra\Exception\NotFoundException('No executional pattern handler matched. '.(is_string($pattern) ? ' ['.$pattern.']' : ''));
	}
}

?>