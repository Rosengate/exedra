<?php namespace Exedra\Application\Map;

class Level extends \ArrayIterator
{
	/**
	 * Reference to the route this level was bound to.
	 * @var \Exedra\Application\Map\Route
	 */
	public $route;

	/**
	 * Factory injected to this level.
	 * @var \Exedra\Application\Map\Factory
	 */
	public $factory;

	public function __construct(Factory $factory, Route $route = null, array $routes = array())
	{
		$this->factory = $factory;
		$this->route = $route;
		if(count($routes) > 0)
			$this->addRoutes($routes);
	}

	/**
	 * Add routes by the given array.
	 * @param array routes
	 */
	public function addRoutes(array $routes)
	{
		foreach($routes as $name=>$routeData)
			$this->addRoute($this->factory->createRoute($this, $name, $routeData));

		return $this;
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
	 * A recursivable functionality to find route under this level, by the given request instance.
	 * @param array of result containing parameter 
	 * @param array passedParameters - highly otional.
	 * @return array {route: \Exedra\Application\Map\Route|false, parameter: array}
	 */
	public function findRoute(\Exedra\HTTP\Request $request, $levelUri, array $passedParameters = array())
	{
		$this->rewind();

		// loop the level and find.
		while($this->valid())
		{
			$route = $this->current();

			$result = $route->validate($request, $levelUri);

			$remainingUri = $route->getRemainingUri($levelUri);

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

					// $subrouteResult = $route->getSubroute()->query($queryUpdated, $passedParameters);
					$subrouteResult = $route->getSubroute()->findRoute($request, $remainingUri, $passedParameters);

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