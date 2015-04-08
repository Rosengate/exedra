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
	protected $level;

	/**
	 * Route and Level factory.
	 * @var \Exedra\Application\Map\Factory
	 */
	public $factory;

	public function __construct(\Exedra\Application\Application $app, \Exedra\Application\Map\Factory $factory)
	{
		$this->app = $app;
		$this->factory = $factory;
		$this->level = $factory->createLevel();
	}

	/**
	 * Add route to the first level on this map.
	 * @param array $routes
	 */
	public function addRoute(array $routes)
	{
		foreach($routes as $name=>$routeData)
			$this->level->addRoute($this->factory->createRoute($this->level, $name, $routeData));

		return $this;
	}

	/**
	 * Add a route on top of other route.
	 * @param string name of the route.
	 * @param array routes
	 * @return this
	 */
	public function addOnRoute($name, array $routes)
	{
		$route = $this->getRoute($name);
		
		// if has subroute, use the that subroute, else, create a new subroute.
		if($route->hasSubroute())
			$route->getSubroute()->addRoutesByArray($routes);
		else
			$route->setSubroute($routes);

		return $this;
	}

	/**
	 * Find route by the absolute name.
	 * @param string name.
	 * @return \Exedra\Application\Map\Finding
	 */
	public function findByName($name, $parameter = array())
	{
		$route = $this->getRoute($name);

		return new \Exedra\Application\Map\Finding($route?:null, $parameter);
	}

	/**
	 * Get route by the given absolute name.
	 * @param string name.
	 * @return \Exedra\Application\Map\Route or false boolean.
	 */
	public function getRoute($name)
	{
		if(isset($this->cache[$name]))
		{
			$route = $this->cache[$name];
		}
		else
		{
			$route = $this->level->findRouteByName($name);

			// save this route.
			$this->cache[$name] = $route;
		}

		return $route;
	}

	/**
	 * Find route by parameters.
	 * @param array query
	 * @return \Exedra\Application\Map\Finding
	 */
	public function find(array $query)
	{
		$result = $this->level->query($query);

		return new \Exedra\Application\Map\Finding($result['route']?:null, $result['parameter']);
	}
}



?>