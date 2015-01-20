<?php namespace Exedra\Application\Map;

class Level extends \ArrayIterator
{
	/* The route it's bound to */
	public $route;

	public function __construct(Route $route = null, array $routes = array())
	{
		$this->route = $route;
		if(count($routes) > 0) 
			foreach($routes as $name=>$routeData)
				$this->addRoute(new Route($this, $name, $routeData));
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

			if($result['route'] != false)
			{
				// if has no subroute OR has subsroute but have execution and remaining uri is ''
				if(!$route->hasSubroute() || ($route->hasSubroute() && $route->hasExecution() && $route->getRemainingUri($query['uri']) == ''))
				{
					// prepare the final parameter by merging the passed parameter, with result parameter.
					$params = array_merge($passedParameters, $result['parameter']);

					return array(
						'route'=> $result['route'], 
						'parameter'=> $params);
				}
				else
				{
					// if has passed parameter.
					$passedParameters = count($result['parameter']) > 0 ? $result['parameter'] : array();

					$queryUpdated = $query;
					$queryUpdated['uri'] = $route->getRemainingUri($query['uri']);

					$subrouteResult = $route->getSubroute()->query($queryUpdated, $passedParameters);

					// if found. else. continue on this level.
					if($subrouteResult['route'] != false)
						return $subrouteResult;

				}
			}

			$this->next();
		}

		// false default.
		return array('route'=> false, 'parameter'=> array());
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