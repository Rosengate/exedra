<?php namespace Exedra\Application\Map;

class Level extends \ArrayIterator
{
	/**
	 * Reference to the route this level was bound to.
	 * @var \Exedra\Application\Map\Route
	 */
	public $route;

	public function __construct(Route $route = null, array $routes = array())
	{
		$this->route = $route;
		if(count($routes) > 0)
			$this->addRoutesByArray($routes);
	}

	/**
	 * Get the route this level was bound to.
	 * @return \Exedra\Application\Map\Route
	 */
	public function getUpperRoute()
	{
		return $this->route;
	}

	/**
	 * Get route by it's absolute name.
	 * @param mixed routeName by dot notation or array.
	 * @return false if not found. else \Exedra\Application\Map\Route
	 */
	public function findRouteByName($routeName)
	{
		$routeNames = !is_array($routeName) ? explode(Route::$notation, $routeName) : $routeName;
		$routeName = array_shift($routeNames);

		$this->rewind();
		// loop this level, and find the route.
		while($this->valid())
		{
			$route = $this->current();

			if($route->getName() == $routeName)
				if(count($routeNames) > 0 && $route->hasSubroute())
					return $route->getSubroute()->findRouteByName($routeNames);
				else
					return $route;

			$this->next();
		}

		return false;
	}

	/**
	 * Query the route under his level, with the given parameter.
	 * @param array of result containing parameter 
	 * @param array passedParameters - highly otional.
	 * @return {route: \Exedra\Application\Map\Route OR false, parameter: array}
	 */
	public function query(array $query, array $passedParameters = array())
	{
		$this->rewind();

		// loop the level and find.
		while($this->valid())
		{
			$route = $this->current();

			$result = $route->validate($query);

			$remainingUri = $route->getRemainingUri($query['uri']);

			$hasSubroute = $route->hasSubroute();

			if(($result['route'] != false) || (($result['equal'] == true || $result['equal'] === null) && ($remainingUri != '' && $hasSubroute)))
			{
				$executionPriority = $route->hasSubroute() && $route->hasExecution() && $remainingUri == '';

				// 1. if found. and no more subroute. OR
				// 2. has subroutes but, has execution, 
				if(!$route->hasSubroute() || $executionPriority)
				{
					// prepare the final parameter by merging the passed parameter, with result parameter.
					$params = array_merge($passedParameters, $result['parameter']);

					return array(
						'route'=> $result['route'], 
						'parameter'=> $params,
						'equal'=> $result['equal']);
				}
				else
				{
					// if has passed parameter.
					$passedParameters = count($result['parameter']) > 0 ? $result['parameter'] : array();

					$queryUpdated = $query;
					$queryUpdated['uri'] = $remainingUri;

					$subrouteResult = $route->getSubroute()->query($queryUpdated, $passedParameters);

					// if found. else. continue on this level.
					if($subrouteResult['route'] != false)
						return $subrouteResult;
				}
			}

			$this->next();
		}

		// false default.
		return array('route'=> false, 'parameter'=> array(), 'equal'=> false);
	}

	/**
	 * Add routes by the given array.
	 * @param array routes
	 */
	public function addRoutesByArray(array $routes)
	{
		foreach($routes as $name=>$routeData)
			$this->addRoute(new Route($this, $name, $routeData));

		return $this;
	}

	/**
	 * Add route to this level.
	 * @param \Exedra\Application\Map\Route
	 * @return this
	 */
	public function addRoute(Route $route)
	{
		$this->append($route);

		return $this;
	}
}