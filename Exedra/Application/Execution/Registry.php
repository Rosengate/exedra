<?php
namespace Exedra\Application\Execution;

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
	 * @var \Exedra\Application\Execution\Handlers
	 */
	public $handlers;

	public function __construct(\Exedra\Application\Execution\Handlers $handlers)
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
	 * @param \Exedra\Application\Execution\Exec
	 * @param mixed pattern
	 * @return \Closure 
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe, $pattern)
	{
		return $this->handlers->resolve($exe, $pattern);
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