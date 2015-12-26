<?php
namespace Exedra\Application\Map;

/**
 * A factory that handle the route/level/finding and http request creation
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
	 * @var \Exedra\Application\Application app
	 */
	protected $app;

	protected $registry = array();

	protected $reflections = array();

	/**
	 * Define explicitness of the routing.
	 * @var bool
	 */
	protected $isExplicit = false;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;

		$this->useDefaultRouting();
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
	 * Register default routing components
	 * @return self
	 */
	public function useDefaultRouting()
	{
		$this->isExplicit = true;

		return $this->register(array(
			'route' => '\Exedra\Application\Map\Route',
			'level' => '\Exedra\Application\Map\Level',
			'finding' => '\Exedra\Application\Map\Finding',
			'request' => '\Exedra\HTTP\Request',
			'exception' => '\Exception'));
	}

	/**
	 * Register Convenient routing components
	 * @return self
	 */
	public function useConvenientRouting()
	{
		$this->isExplicit = false;

		return $this->register(array(
			'route' => '\Exedra\Application\Map\Convenient\Route',
			'level' => '\Exedra\Application\Map\Convenient\Group'
			));
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
	 * @param \Exedra\Application\Map\Route
	 * @param string pattern
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevelByPattern(Route $route = null, $pattern)
	{
		// has structure based path
		if(strpos($pattern, ':') !== FALSE)
		{
			list($structure, $path) = explode(':', $pattern);
			$loadParameter = array(
				'structure' => $structure,
				'path' => $path
				);
		}
		else
		{
			$loadParameter = $pattern;
		}

		$subroutes = $this->getLoader()->load($loadParameter);

		return $this->createLevel($route, $subroutes);
	}

	/**
	 * Create finding object
	 * @param \Exedra\Application\Map\Route result's route.
	 * @param array parameters
	 * @param \Exedra\HTTP\Request
	 * @return \Exedra\Application\Map\Finding
	 */
	public function createFinding(Route $route = null, array $parameters = null, \Exedra\HTTP\Request $request = null)
	{
		return $this->create('finding', array($route, $parameters, $request, $this->app->config));
		return new Finding($route, $parameters, $request);
	}

	/**
	 * Create request required by map-routing related matters.
	 * @return \Exedra\HTTP\Request
	 */
	public function createRequest(array $query = array())
	{
		return $this->create('request', array($query));
		return new \Exedra\HTTP\Request($query);
	}

	public function throwException($message)
	{
		throw $this->create('exception', array($message));
	}
}