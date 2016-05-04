<?php
namespace Exedra\Application\Map;

/**
 * A factory that handle the route/level/finding and HTTP request creation
 * This instance is injected into each level created
 */

class Factory
{
	/**
	 * Loader for lazy loading functionality
	 */
	protected $loader;

	/**
	 * Application instance
	 * @var \Exedra\Application app
	 */
	protected $app;

	protected $registry = array();

	protected $reflections = array();

	/**
	 * Define explicitness of the routing.
	 * @var bool
	 */
	protected $isExplicit = false;

	public function __construct(\Exedra\Application $app)
	{
		$this->app = $app;

		$this->registerRoutingComponents();
	}

	/**
	 * Get middleware registry from \Exedra\Application
	 * @return \Exedra\Application\Middleware\Registry
	 */
	public function getMiddlewareRegistry()
	{
		return $this->app->middleware;
	}

	/**
	 * Get application loader
	 * @return \Exedra\Loader
	 */
	public function getLoader()
	{
		return $this->app->loader;
	}

	public function isExplicit()
	{
		return $this->isExplicit;
	}

	/**
	 * Register basic components [Finding, ServerRequest, Exception]
	 */
	protected function registerRoutingComponents()
	{
		$this->register(array(
			'finding' => '\Exedra\Application\Map\Finding',
			'route' => '\Exedra\Application\Map\Route',
			'level' => '\Exedra\Application\Map\Convenient',
			'request' => '\Exedra\Http\ServerRequest',
			'exception' => '\Exception'
			));

		return $this;
	}

	/**
	 * Register classname
	 * @param string name
	 * @param string classname
	 */
	public function register(array $registry)
	{
		foreach($registry as $name => $classname)
		{
			$this->registry[$name] = $classname;
			unset($this->reflections[$name]);
		}

		return $this;
	}

	/**
	 * General method to create classes from the registered list.
	 * @param string name
	 * @param array arguments
	 */
	public function create($name, array $arguments = array())
	{
		if(!isset($this->reflections[$name]))
			$this->reflections[$name] = new \ReflectionClass($this->registry[$name]);

		$reflection = $this->reflections[$name];

		return $reflection->newInstanceArgs($arguments);
	}

	/**
	 * Create route object
	 * @param \Exedra\Application\Map\Level level of where the route is based on
	 * @param string route name
	 * @param array parameters route parameter
	 * @return \Exedra\Application\Map\Route
	 */
	public function createRoute(Level $level, $name, array $parameters)
	{
		return $this->create('route', array($level, $name, $parameters));
	}

	/**
	 * Create level object
	 * @param \Exedra\Application\Map\Route route of where the level is based on
	 * @param array of routes
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevel(Route $route = null, array $routes = array())
	{
		return $this->create('level', array($this, $route, $routes));
		return new Level($this, $route, $routes);
	}

	/**
	 * Create level by pattern
	 * For now, assume the passed pattern as string
	 * @param \Exedra\Application\Map\Route
	 * @param string pattern
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevelByPattern(Route $route = null, $pattern)
	{
		$closure = $this->getLoader()->load($pattern);

		// expecting a Map\Level from this loaded file.
		if(!($closure instanceof \Closure))
			return $this->throwException('Expecting closure for the subroutes');

		$level = $this->createLevel($route);

		$closure($level);

		return $level;
	}

	/**
	 * Create finding object
	 * @param \Exedra\Application\Map\Route result's route.
	 * @param array parameters
	 * @param \Exedra\Http\Request
	 * @return \Exedra\Application\Map\Finding
	 */
	public function createFinding(Route $route = null, array $parameters = null, \Exedra\Http\ServerRequest $request = null)
	{
		return $this->create('finding', array($route, $parameters, $request, $this->app->config));
		return new Finding($route, $parameters, $request);
	}

	/**
	 * Create request required by map-routing related matters.
	 * @return \Exedra\Http\Request
	 */
	public function createRequest(array $query = array())
	{
		return \Exedra\Http\ServerRequest::createFromArray($query);
		return $this->create('request', array($query));
		return new \Exedra\Http\ServerRequest($query);
	}

	public function throwException($message)
	{
		throw $this->create('exception', array($message));
	}
}