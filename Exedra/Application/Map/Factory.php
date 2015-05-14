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

	public function __construct(\Exedra\Loader $loader)
	{
		$this->loader = $loader;
		$this->initiate();
	}

	/**
	 * Initiate default classes
	 */
	protected function initiate()
	{
		$this->register('route', '\Exedra\Application\Map\Route');
		$this->register('level', '\Exedra\Application\Map\Level');
		$this->register('finding', '\Exedra\Application\Map\Finding');
		$this->register('request', '\Exedra\HTTP\Request');
		$this->register('exception', '\Exception');
	}

	/**
	 * Register classname
	 * @param string name
	 * @param string classname
	 */
	public function register($name, $classname)
	{
		$this->registries[$name] = new \ReflectionClass($classname);
	}

	/**
	 * General method to create classes from the registered list.
	 * @param string name
	 * @param array arguments
	 */
	public function create($name, array $arguments = array())
	{
		$reflection = $this->registries[$name];

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

		$subroutes = $this->loader->load($loadParameter);

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
		return $this->create('finding', array($route, $parameters, $request));
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