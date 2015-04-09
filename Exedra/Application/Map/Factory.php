<?php
namespace Exedra\Application\Map;

/**
 * A factory that handle the route creation
 * This instance is injected into both of each route and level.
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
		return new Route($level, $name, $parameters);
	}

	/**
	 * Create level object
	 * @param \Exedra\Application\Map\Route route of where the level is based on
	 * @param array of routes
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevel(Route $route = null, array $routes = array())
	{
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
}