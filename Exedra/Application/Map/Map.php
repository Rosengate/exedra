<?php namespace Exedra\Application\Map;

class Map
{
	/**
	 * Cache storage.
	 * @var array
	 */
	private $cache = array();

	/**
	 * First level of this map.
	 * @var \Exedra\Application\Map\Level
	 */
	protected $level;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;
		$this->level = new Level;
	}

	/*public function onRoute($routeName,$action,$param)
	{
		list($action,$actionExecution)	= explode(":",$action);

		switch($action)
		{
			case "bind":
			$this->bindRoute($routeName,$actionExecution,$param);
			break;
		}
	}*/

	/**
	 * Add route to the first level on this map.
	 * @param array $routes
	 */
	public function addRoute(array $routes)
	{
		foreach($routes as $name=>$routeData)
			$this->level->addRoute(new Route($this->level, $name, $routeData));

		return $this;
	}

	/**
	 * Add a route on top of other route.
	 * @param string name of the route.
	 * @param array routes
	 */
	public function addOnRoute($name, array $routes)
	{
		$route = $this->findByName($name);

		if(!$route)
			throw new \Exception('Route by name '. $name .' was not found.');
		
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
	 * @return route or false.
	 */
	public function findByName($name)
	{
		if(isset($this->cache[$name]))
		{
			$route = $this->cache[$name];
		}
		else
		{
			$route = $this->level->findRouteByName($name);

			// save this finding.
			$this->cache[$name] = $route;
		}

		return $route ? $route : false ;
	}

	/**
	 * Alias to findByName
	 * @param string name.
	 * @return route or false.
	 */
	public function getRoute($name)
	{
		return $this->findByName($name);
	}

	/**
	 * Find route by parameters.
	 * @param array query
	 * @return array(
	 *  	route => \Exedra\Application\Map\Route OR false if not found
	 *		parameter => array
	 *				)
	 */
	public function find(array $query)
	{
		$result = $this->level->query($query);

		// rebuild
		return array(
			'route'=>$result['route'],
			'parameter'=>isset($result['parameter']) ? $result['parameter'] : array()
			);
	}
}



?>