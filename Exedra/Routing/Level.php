<?php namespace Exedra\Routing;

use Exedra\Exception\InvalidArgumentException;
use Exedra\Http\ServerRequest;
use Exedra\Routing\Factory;
use Exedra\Routing\Finding;
use Exedra\Routing\Route;

class Level implements \ArrayAccess
{
	/**
	 * Reference to the route this level was bound to.
	 * @var Route
	 */
	public $route;

	/**
	 * Cached routes
	 * @var array routes
	 */
	protected $routes = array();

	/**
	 * Level based middlewares
	 * Addable on level not bound to any route
	 * @var array $middlewares
	 */
	protected $middlewares = array();

	/**
	 * Routing based handlers
	 * @var array handlers
	 */
	protected $handlers = array();

	/**
	 * Factory injected to this level.
	 * @var Factory
	 */
	public $factory;

    /**
     * @var array|Route[]
     */
    protected $storage = array();

	public function __construct(Factory $factory, Route $route = null, array $routes = array())
	{
		$this->factory = $factory;

		$this->route = $route;

		if(count($routes) > 0)
			$this->addRoutes($routes);
	}

	/**
	 * Change level factory.
	 * Subroutes behaviour are expected to change
	 * Accept fully qualied class name or an instance of Factory.
	 * @param Factory|string $factory instance, or string of the class name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setFactory($factory)
	{
		if(is_string($factory))
			$factory = new $factory($this->factory->getLookupPath());

		if(!($factory instanceof Factory))
			throw new InvalidArgumentException('The map factory must be the the type of [\Exedra\Routing\Factory].');

		$this->factory = $factory;
	}

	/**
	 * Get factory instance this Level is based on
	 * @return Factory
	 */
	public function getFactory()
	{
		return $this->factory;
	}

	/**
	 * Inversely set middleware on upper route
	 * If there's this level is on the top (not route dependant), register middleware on app
	 * @param mixed $middleware
	 * @return $this
	 */
	public function setMiddleware($middleware)
	{
		if($this->route)
			$this->route->setMiddleware($middleware);
		else
			$this->middlewares = array($middleware);

		return $this;
	}

	/**
	 * Add a routing based handler
	 * To be stacked on runtime
	 * @param string $name
	 * @param string|\Closure $handler
	 * @return self
	 */
	public function addHandler($name,  $handler)
	{
		$this->handlers[$name] = $handler;

		 return $this;
	}

	/**
	 * Alias to addHandler()
	 * @param string $name
	 * @param string|\Closure $handler
	 * @return self
	 */
	public function handler($name, $handler)
	{
		$this->handlers[$name] = $handler;

		return $this;
	}

	/**
	 * Get all handlers
	 * @return array
	 */
	public function getHandlers()
	{
		return $this->handlers;
	}

	/**
	 * Alias to addMiddleware
	 * @param mixed $middleware
	 * @return $this
	 */
	public function middleware($middleware)
	{
		return $this->addMiddleware($middleware);
	}

	/**
	 * Inversely add middleware on upper route
	 * If there's this level is on the top (not route dependant), register middleware on app
	 * @param mixed $middleware
	 * @return $this
	 */
	public function addMiddleware($middleware)
	{
		if($this->route)
			$this->route->addMiddleware($middleware);
		else
			$this->middlewares[] = $middleware;

		return $this;
	}

	/**
	 * Get Level based middlewares
	 * @return array
	 */
	public function getMiddlewares()
	{
		return $this->middlewares;
	}

    /**
     * Add routes by the given array.
     * @param array $routes
     * @return $this
     */
	public function addRoutes(array $routes)
	{
		foreach($routes as $name => $routeData)
			$this->addRoute($this->routes[$name] = $this->factory->createRoute($this, $name, $routeData));

		return $this;
	}

	/**
	 * Add subroutes on other route.
	 * @param string $name of the route.
	 * @param array $routes
	 * @return $this
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
	 * @param Route $route
	 * @return $this
	 */
	public function addRoute(Route $route)
	{
	    $this->storage[] = $route;

		return $this;
	}

	/**
	 * Get the route this level was bound to.
	 * @return Route
	 */
	public function getUpperRoute()
	{
		return $this->route;
	}

	/**
	 * Make a finding by \Exedra\Http\Request
	 * @param ServerRequest $request
	 * @return Finding
	 */
	public function findByRequest(ServerRequest $request)
	{
		$result = $this->findRouteByRequest($request, trim($request->getUri()->getPath(), '/'));

		return $this->factory->createFinding($result['route'] ? : null, $result['parameter'], $request);
	}

	/**
	 * Make a finding by given absolute name
	 * @param string $name.
	 * @param array $parameters
	 * @param ServerRequest $request forwarded request, for this Finding
	 * @return Finding
	 */
	public function findByName($name, array $parameters = array(), ServerRequest $request = null)
	{
		$route = $this->findRoute($name);

		return $this->factory->createFinding($route ? : null, $parameters, $request);
	}

    /**
     * Loop the routes within this level and it's sublevel
     * Break on other closure result not equal to null
     * @param \Closure $closure
     * @param bool $recursive
     * @return mixed|null
     */
	public function each(\Closure $closure, $recursive = true)
	{
        foreach($this->storage as $route)
		{
			$result = $closure($route);

			if($result !== null)
				return $result;
			
			if($route->hasSubroutes() && $recursive)
			{
				$result = $route->getSubroutes()->each($closure);

				if($result !== null)
					return $result;
			}
		}

		return null;
	}

	/**
	 * Find route given by an absolute search string relative to this level
	 * This method also cache the finding result
	 * Example :
	 * - general.books.detail
	 * - general.#bookDetail.comments
	 * @param mixed $name by dot notation or array.
	 * @return Route|false
	 */
	public function findRoute($name)
	{
		if(isset($this->routes[$name]))
			return $this->routes[$name];
		else
			return $this->routes[$name] = $this->findRouteRecursively($name);
	}

	/**
	 * A recursive search of route given by an absolute search string relative to this level
	 * @param string $routeName
	 * @return Route|false
	 */
	protected function findRouteRecursively($routeName)
	{
		$routeNames = !is_array($routeName) ? explode('.', $routeName) : $routeName;
		$routeName = array_shift($routeNames);
		$isTag = strpos($routeName, '#') === 0;

		// search by route name
		if(!$isTag)
		{
			// loop this level, and find the route.

            foreach($this->storage as $route)
            {
                if($route->getName() === $routeName)
                    if(count($routeNames) > 0 && $route->hasSubroutes())
                        return $route->getSubroutes()->findRouteRecursively($routeNames);
                    else
                        return $route;
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
	 * @param string $tag
	 * @return Route|null
	 */
	public function findRouteByTag($tag)
	{
		$route = $this->each(function(Route $route) use($tag)
		{
			if($route->hasProperty('tag') && $route->getProperty('tag') == $tag)
				return $route;
		});

		return $route ? : null;
	}

	/**
	 * A recursivable functionality to find route under this level, by the given request instance.
	 * @param ServerRequest $request
     * @param string $levelUriPath
	 * @param array $passedParameters - highly otional.
	 * @return array
     * {route: boolean|Route, parameter: array, continue: boolean}
	 */
	public function findRouteByRequest(ServerRequest $request, $levelUriPath, array $passedParameters = array())
	{
		// loop the level and find.
        foreach($this->storage as $route)
		{
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
					$passedParameters = array_merge(count($result['parameter']) > 0 ? $result['parameter'] : array(), $passedParameters);

					$subrouteResult = $route->getSubroutes()->findRouteByRequest($request, $remainingPath, $passedParameters);

					// if found. else. continue on this level.
					if($subrouteResult['route'] != false)
						return $subrouteResult;
				}
			}
		}

		// false default.
		return array('route'=> false, 'parameter'=> array(), 'continue' => false);
	}

    /**
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return isset($this->routes[$name]);
    }

    /**
     * A level invoke to conveniently
     * create an empty route with the optional name
     * @param string $name
     * @return Route
     */
    public function offsetGet($name)
    {
        if(isset($this->routes[$name]))
            return $this->routes[$name];

        $route = $this->factory->createRoute($this, $name, array());

        $this->addRoute($route);

        return $this->routes[$name] = $route;
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}