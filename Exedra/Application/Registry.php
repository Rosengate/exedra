<?php
namespace Exedra\Application;

/**
 * Handle the registered things on application layer for execution layer..
 */

class Registry
{
	/**
	 * List of middleware
	 * @var array
	 */
	protected $middlewares = array();

	/**
	 * String of route name
	 */
	protected $failRoute = null;

	/**
	 * Pattern class
	 * @var \Exedra\Application\Execution\Pattern
	 */
	public $pattern;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->pattern = new \Exedra\Application\Execution\Pattern($app);
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
	 * Get fail route
	 * @return string|null
	 */
	public function getFailRoute()
	{
		return $this->failRoute;
	}

	/**
	 * Add execution middleware.
	 * @param mixed closure
	 */
	public function addMiddleware($closure)
	{
		$this->middlewares[] = $closure;
	}

	/**
	 * has middleware or not.
	 * @return boolean.
	 */
	public function hasMiddlewares()
	{
		return count($this->middlewares) > 0;
	}

	/**
	 * Get all these middlewares.
	 * @return array of closed middlewares.
	 */
	public function getMiddlewares()
	{
		return $this->middlewares;
	}
}

?>