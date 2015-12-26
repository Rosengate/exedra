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
	 * Handlers registry class
	 * @var \Exedra\Application\Execution\Handlers
	 */
	public $handlers;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;

		$this->handlers = new \Exedra\Application\Execution\Handlers($app);
		
		$this->registerDefaultHandlers();
	}

	/**
	 * Register default handlers
	 * @return void
	 */
	protected function registerDefaultHandlers()
	{
		$this->handlers->register('closure', '\Exedra\Application\Execution\Handler\Closure');
		$this->handlers->register('controller', '\Exedra\Application\Execution\Handler\Controller');
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
		// wire back to middleware registry for backward fix
		$this->app->middleware->add($closure);

		// $this->middlewares[] = $closure;
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