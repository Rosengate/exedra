<?php namespace Exedra\Application\Map;

class Map
{
	/**
	 * Cache storage.
	 * @var array
	 */
	protected $cache = array();

	/**
	 * First level of this map.
	 * @var \Exedra\Application\Map\Level
	 */
	public $level;

	/**
	 * Route and Level factory.
	 * @var \Exedra\Application\Map\Factory
	 */
	public $factory;

	public function __construct(\Exedra\Application\Map\Factory $factory)
	{
		$this->factory = $factory;
		$this->level = $factory->createLevel();
	}

	/**
	 * Add list of routes by array to the first level on this map.
	 * @param array routes
	 */
	public function addRoutes(array $routes)
	{
		$this->level->addRoutes($routes);

		return $this;
	}

	/**
	 * Add subroutes on other route.
	 * @param string name of the route.
	 * @param array routes
	 * @return this
	 */
	public function addOnRoute($name, array $routes)
	{
		$route = $this->findRoute($name);
		
		// if has subroutes, use the that subroutes, else, create a new subroute.
		if($route->hasSubroutes())
			$route->getSubroutes()->addRoutes($routes);
		else
			$route->setSubroutes($routes);

		return $this;
	}

	/**
	 * Find route by the absolute name.
	 * @param string name.
	 * @return \Exedra\Application\Map\Finding
	 */
	public function findByName($name, $parameters = array())
	{
		$route = $this->findRoute($name);

		return $this->factory->createFinding($route ? : null, $parameters);
	}

	/**
	 * Get Route by the given absolute name.
	 * @param string name.
	 * @return \Exedra\Application\Map\Route|false
	 */
	public function findRoute($name)
	{
		if(isset($this->cache[$name]))
		{
			$route = $this->cache[$name];
		}
		else
		{
			$route = $this->level->findRoute($name);

			// save this route.
			$this->cache[$name] = $route;
		}

		return $route;
	}

	/**
	 * Find route by \Exedra\HTTP\request or array
	 * @param \Exedra\HTTP\Request|array
	 * @return \Exedra\Application\Map\Finding
	 */
	public function find($request)
	{
		if(!($request instanceof \Exedra\HTTP\Request))
			if(is_array($request))
				$request = $this->factory->createRequest($request);
			else
				return $this->factory->throwException('Argument for map::find() must be either array or \Exedra\HTTP\Request');
				
		$result = $this->level->findRouteByRequest($request, $request->getUri());

		return $this->factory->createFinding($result['route'] ? : null, $result['parameter'], $request);
	}
}



?>