<?php namespace Exedra\Application\Map;

class Level extends \ArrayIterator
{
	/**
	 * Reference to the route this level was bound to.
	 * @var \Exedra\Application\Map\Route
	 */
	public $route;

	/**
	 * Route finding cache
	 * @var array routeCache
	 */
	protected $routeCache = array();

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
	 * Add route to this level.
	 * @param \Exedra\Application\Map\Route
	 * @return this
	 */
	public function addRoute(Route $route)
	{
		$this->append($route);

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
	 * Make a finding by \Exedra\HTTP\Request or array
	 * @param \Exedra\HTTP\Request
	 * @return \Exedra\Application\Map\Finding
	 */
	public function find($request)
	{
		if(!($request instanceof \Exedra\HTTP\Request))
			if(is_array($request))
				$request = $this->factory->createRequest($request);
			else
				return $this->factory->throwException('Argument for map::find() must be either array or \Exedra\HTTP\Request');

		$result = $this->findRouteByRequest($request, $request->getUriPath());

		return $this->factory->createFinding($result['route'] ? : null, $result['parameter'], $request);
	}

	/**
	 * Make a finding by given absolute name
	 * @param string name.
	 * @return \Exedra\Application\Map\Finding
	 */
	public function findByName($name, $parameters = array())
	{
		$route = $this->findRoute($name);

		return $this->factory->createFinding($route ? : null, $parameters);
	}

	/**
	 * Loop the routes within this level and it's sublevel
	 * Break on other closure result not equal to null
	 * @param \Closure closure
	 * @return null|mixed
	 */
	public function each(\Closure $closure)
	{
		$this->rewind();

		while($this->valid())
		{
			$route = $this->current();

			$result = $closure($route);

			if($result !== null)
				return $result;
			
			if($route->hasSubroutes())
			{
				$result = $route->getSubroutes()->each($closure);

				if($result !== null)
					return $result;
			}

			$this->next();
		}

		return null;
	}

	/**
	 * Find route given by an absolute search string relative to this level
	 * This method also cache the finding result
	 * Example :
	 * - general.books.detail
	 * - general.#bookDetail.comments
	 * @param mixed routeName by dot notation or array.
	 * @return \Exedra\Application\Map\Route|false
	 */
	public function findRoute($name)
	{
		if(isset($this->routeCache[$name]))
			return $this->routeCache[$name];
		else
			return $this->routeCache[$name] = $this->findRouteRecursively($name);
	}

	/**
	 * A recursive search of route given by an absolute search string relative to this level
	 * @param string routeName
	 * @return \Exedra\Application\Map\Route|false
	 */
	protected function findRouteRecursively($routeName)
	{
		$routeNames = !is_array($routeName) ? explode(Route::$notation, $routeName) : $routeName;
		$routeName = array_shift($routeNames);
		$isTag = strpos($routeName, '#') === 0;

		$this->rewind();

		// search by route name
		if(!$isTag)
		{
			// loop this level, and find the route.
			while($this->valid())
			{
				$route = $this->current();

				if($route->getName() === $routeName)
					if(count($routeNames) > 0 && $route->hasSubroutes())
						return $route->getSubroutes()->findRouteRecursively($routeNames);
					else
						return $route;

				$this->next();
			}
		}
		// search by route tag under this level.
		else
		{
			$route = $this->findRouteByTag(substr($routeName, 1));

			if($route)
			{
				if(count($routeNames) > 0 && $route->hasSubroutes())
					return $route->getSubroutes()->findRouteRecursively($routeNames);
				else
					return $route;
			}
		}

		return false;
	}

	/**
	 * @param string tag
	 * @return \Exedra\Application\Map\Route|null
	 */
	public function findRouteByTag($tag)
	{
		$route = $this->each(function($route) use($tag)
		{
			if($route->hasProperty('tag') && $route->getProperty('tag') == $tag)
				return $route;
		});

		return $route ? : null;
	}

	/**
	 * A recursivable functionality to find route under this level, by the given request instance.
	 * @param array of result containing parameter 
	 * @param array passedParameters - highly otional.
	 * @return array {route: \Exedra\Application\Map\Route|false, parameter: array}
	 */
	public function findRouteByRequest(\Exedra\HTTP\Request $request, $levelUriPath, array $passedParameters = array())
	{
		$this->rewind();

		// loop the level and find.
		while($this->valid())
		{
			$route = $this->current();

			$result = $route->validate($request, $levelUriPath);

			$remainingPath = $route->getRemainingPath($levelUriPath);

			$hasSubroutes = $route->hasSubroutes();

			// if have found, or to do a deeper search
			if(($result['route'] != false) || ($result['continue'] === true && ($remainingPath != '' && $hasSubroutes)))
			{
				$executionPriority = $route->hasSubroutes() && $route->hasExecution() && $remainingPath == '';

				// 1. if found. and no more subroute. OR
				// 2. has subroutes but, has execution, 
				if(!$route->hasSubroutes() || $executionPriority)
				{
					// prepare the final parameter by merging the passed parameter, with result parameter.
					$params = array_merge($passedParameters, $result['parameter']);

					return array(
						'route'=> $result['route'], 
						'parameter'=> $params,
						'continue'=> $result['continue']);
				}
				else
				{
					// if has passed parameter.
					$passedParameters = count($result['parameter']) > 0 ? $result['parameter'] : array();

					// $subrouteResult = $route->getSubroutes()->query($queryUpdated, $passedParameters);
					$subrouteResult = $route->getSubroutes()->findRouteByRequest($request, $remainingPath, $passedParameters);

					// if found. else. continue on this level.
					if($subrouteResult['route'] != false)
						return $subrouteResult;
				}
			}

			$this->next();
		}

		// false default.
		return array('route'=> false, 'parameter'=> array(), 'continue' => false);
	}
}