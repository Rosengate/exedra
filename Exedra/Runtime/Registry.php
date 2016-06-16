<?php
namespace Exedra\Runtime;

/**
 * Handle the registered things on application layer for execution layer..
 */

class Registry
{
	/**
	 * String of route name
	 * @var string failRoute
	 */
	protected $failRoute = null;

	/**
	 * Handlers registry class
	 * @var \Exedra\Runtime\Handlers
	 */
	public $handlers;

	public function __construct(\Exedra\Runtime\Handlers $handlers)
	{
		$this->handlers = $handlers;
	}

	/**
	 * Set fail route, to be used.
	 * @param string routeName
	 */
	public function setFailRoute($routeName)
	{
		$this->failRoute = $routeName;
	}

	/**
	 * Resolve the execution handle pattern
	 * @param \Exedra\Runtime\Exe
	 * @param mixed pattern
	 * @return \Closure 
	 */
	public function resolve(\Exedra\Runtime\Exe $exe, $pattern, array $handlers = array())
	{
		return $this->handlers->resolve($exe, $pattern, $handlers);
	}

	/**
	 * Get fail route
	 * @return string|null
	 */
	public function getFailRoute()
	{
		return $this->failRoute;
	}

	/**
	 * Alias to pattern->register(name, class)
	 * @return self
	 */
	public function addHandler($name, $class)
	{
		$this->handlers->register($name, $class);

		return $this;
	}
}

?>