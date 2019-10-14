<?php namespace Exedra\Routing;

use Exedra\Contracts\Routing\RouteValidator;
use Exedra\Contracts\Routing\Registrar;
use Exedra\Exception\Exception;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\RouteNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

class Group implements \ArrayAccess, Registrar
{
    /**
     * Reference to the route this group was bound to.
     * @var Route
     */
    public $route;

    /**
     * Cached routes
     * @var array routes
     */
    protected $routes = array();

    /**
     * Alias for route name
     * @var array
     */
    protected $aliasIndices = array();

    /**
     * Alias for group
     * @var array
     */
    protected $groupAliasIndices = array();

    /**
     * Routing group based middlewares
     * Addable on group not bound to any route
     * @var array $middlewares
     */
    protected $middlewares = array();

    /**
     * Registered execute handlers
     * @var array handlers
     */
    protected $executeHandlers = array();

    /**
     * Factory injected to this group.
     * @var Factory
     */
    public $factory;

    /**
     * @var array|Route[]
     */
    protected $storage = array();

    /**
     * A request dispatch fail handling route name
     * @var string|null $failRoute
     */
    protected $failRoute = null;

    /**
     * @var RouteValidator[]
     */
    protected $validators = [];

    public function __construct(Factory $factory, Route $route = null, array $routes = array())
    {
        $this->factory = $factory;

        $this->route = $route;

        if (count($routes) > 0)
            $this->addRoutes($routes);
    }

    /**
     * Change routing group factory.
     * Subroutes behaviour are expected to change
     * Accept fully qualied class name or an instance of Factory.
     * @param Factory|string $factory instance, or string of the class name.
     *
     * @throws InvalidArgumentException
     */
    public function setFactory($factory)
    {
        if (is_string($factory))
            $factory = new $factory($this->factory->getLookupPath());

        if (!($factory instanceof Factory))
            throw new InvalidArgumentException('The map factory must be the the type of [\Exedra\Routing\Factory].');

        $this->factory = $factory;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function setFailRoute($name)
    {
        return $this->failRoute = $name;
    }

    /**
     * @return null|string
     */
    public function getFailRoute()
    {
        return $this->failRoute;
    }

    /**
     * @return bool
     */
    public function hasFailRoute()
    {
        return $this->failRoute ? true : false;
    }

    /**
     * Get factory instance this Group is based on
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Inversely set middleware on upper route
     * If there's this group is on the top (not route dependant), register middleware on app
     * @param mixed $middleware
     * @return $this
     */
    public function setMiddleware($middleware)
    {
        if ($this->route)
            $this->route->setMiddleware($middleware);
        else
            $this->middlewares = array(array($middleware));

        return $this;
    }

    /**
     * Add an execute handler
     * @param string $name
     * @param string|\Closure $handler
     * @return self
     */
    public function addExecuteHandler($name, $handler)
    {
        $this->executeHandlers[$name] = $handler;

        return $this;
    }

    /**
     * Get all handlers
     * @return array
     */
    public function getExecuteHandlers()
    {
        return $this->executeHandlers;
    }

    /**
     * Alias to addMiddleware
     * @param mixed $middleware
     * @param array $properties
     * @return $this
     */
    public function middleware($middleware, array $properties = array())
    {
        return $this->addMiddleware($middleware, $properties);
    }

    /**
     * Inversely add middleware on upper route
     * If there's this group is on the top (not route dependant), register middleware on app
     * @param mixed $middleware
     * @param array $properties
     * @return $this
     */
    public function addMiddleware($middleware, array $properties = array())
    {
        if ($this->route) {
            $this->route->addMiddleware($middleware, $properties);
        } else {
            $this->middlewares[] = array($middleware, $properties);
        }

        return $this;
    }

    /**
     * @param array|callable[] $middlewares
     * @return $this
     */
    public function addMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $id => $middleware) {
            // assuming the second entry is the properties
            if (is_array($middleware))
                $this->addMiddleware($middleware[0], $middleware[1]);
            else
                $this->addMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Get routing group based middlewares
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
        foreach ($routes as $name => $routeData)
            $this->addRoute($this->factory->createRoute($this, $name, $routeData));

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
        if ($route->hasSubroutes())
            $route->getSubroutes()->addRoutes($routes);
        else
            $route->setSubroutes($routes);

        return $this;
    }

    /**
     * Add route to this group.
     * @param Route $route
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $this->routes[$route->getName()] = $route;

        $this->storage[] = $route;

        return $this;
    }

    /**
     * Get the route this routing group was bound to.
     * @return Route
     */
    public function getUpperRoute()
    {
        return $this->route;
    }

    /**
     * Make a finding by \Exedra\Http\Request
     * @param ServerRequestInterface $request
     * @return Finding
     * @throws RouteNotFoundException
     */
    public function findByRequest(ServerRequestInterface $request)
    {
        $result = $this->findRouteByRequest($request, trim($request->getUri()->getPath(), '/'));

        if (!$result['route'])
            throw new RouteNotFoundException('Route for request ' . $request->getMethod() . ' ' . $request->getUri()->getPath() . ' does not exist');

        return $this->factory->createFinding($result['route'], $result['parameter'], $request);
    }

    /**
     * Loop the routes within this routing group
     * Break on other closure result not equal to null
     * @param \Closure $closure
     * @param bool $deep
     * @return mixed|null
     */
    public function each(\Closure $closure, $deep = false)
    {
        foreach ($this->storage as $route) {
            $result = $closure($route);

            if ($result !== null)
                return $result;

            if ($route->hasSubroutes() && $deep) {
                $result = $route->getSubroutes()->each($closure, true);

                if ($result !== null)
                    return $result;
            }
        }

        return null;
    }

    /**
     * Find route given by an absolute search string relative to this group
     * This method also cache the finding result
     * Example :
     * - general.books.detail
     * - general.#bookDetail.comments
     * @param mixed $name by dot notation or array.
     * @return Route|false
     */
    public function findRoute($name)
    {
        if (isset($this->routes[$name]))
            return $this->routes[$name];

        // alias check
        if (isset($this->aliasIndices[$name])) {
            $name = $this->aliasIndices[$name];
        } else {
            foreach ($this->groupAliasIndices as $alias => $routeName) {
                if (strpos($name, $alias) === 0) {
                    $name = substr_replace($name, $routeName, 0, strlen($alias));
                    break;
                }
            }
        }

        if ($route = $this->findRouteRecursively($name))
            return $this->routes[$name] = $route;

        return false;
    }

    /**
     * A recursive search of route given by an absolute search string relative to this group
     * @param string $routeName
     * @return Route|false
     */
    protected function findRouteRecursively($routeName)
    {
        // alias check
        if (is_string($routeName)) {
            if (isset($this->aliasIndices[$routeName])) {
                $routeName = $this->aliasIndices[$routeName];
            } else {
                foreach ($this->groupAliasIndices as $alias => $name) {
                    if (strpos($routeName, $alias) === 0) {
                        $routeName = substr_replace($routeName, $name, 0, strlen($alias));
                        break;
                    }
                }
            }
        }

        $routeNames = !is_array($routeName) ? explode('.', $routeName) : $routeName;
        $routeName = array_shift($routeNames);
        $isTag = strpos($routeName, '#') === 0;

        // search by route name
        if (!$isTag) {
            // loop this group, and find the route.
            foreach ($this->storage as $route) {
                if ($route->getName() === $routeName) {
                    // still has depth
                    if (count($routeNames) > 0)
                        if ($route->hasSubroutes())
                            return $route->getSubroutes()->findRouteRecursively($routeNames);
                        else
                            return false;

                    return $route;
                }
            }
        } // search by route tag under this group.
        else {
            $route = $this->findRouteByTag(substr($routeName, 1));

            if ($route) {
                if (count($routeNames) > 0)
                    if ($route->hasSubroutes())
                        return $route->getSubroutes()->findRouteRecursively($routeNames);
                    else
                        return false;

                return $route;
            }
        }

        return false;
    }

    /**
     * Find route by given tag
     *
     * @param string $tag
     * @return Route|null
     */
    public function findRouteByTag($tag)
    {
        $route = $this->each(function (Route $route) use ($tag) {
            if ($route->hasProperty('tag') && $route->getProperty('tag') == $tag)
                return $route;
        }, true);

        return $route ?: null;
    }

    protected function validate($route, $request, $groupUriPath)
    {
        foreach ($this->validators as $validator) {
            if (!$validator->validate($route, $request, $groupUriPath))
                return false;
        }

        return true;
    }

    /**
     * Alias to addValidator
     *
     * @param RouteValidator $validator
     * @return Group
     */
    public function addValidator(RouteValidator $validator)
    {
        $this->validators[] = $validator;

        return $this;
    }

    /**
     * A recursivable functionality to find route under this routing group, by the given request instance.
     * @param ServerRequestInterface $request
     * @param string $groupUriPath
     * @param array $passedParameters - highly otional.
     * @return array
     * {route: boolean|Route, parameter: array, continue: boolean}
     */
    protected function findRouteByRequest(ServerRequestInterface $request, $groupUriPath, array $passedParameters = array())
    {
        // loop the group and find.
        foreach ($this->storage as $route) {
            if (count($this->validators) > 0 && !$this->validate($route, $request, $groupUriPath))
                return array(
                    'route' => false,
                    'parameter' => array(),
                    'continue' => false
                );

            $result = $route->match($request, $groupUriPath);

            $remainingPath = $route->getRemainingPath($groupUriPath);

            $hasSubroutes = $route->hasSubroutes();

            // if have found, or to do a deeper search
            if (($result['route'] != false) || ($result['continue'] === true && ($remainingPath != '' && $hasSubroutes))) {
                $executionPriority = $route->hasSubroutes() && $route->hasExecution() && $remainingPath == '';

                // 1. if found. and no more subroute. OR
                // 2. has subroutes but, has execution,
                if (!$route->hasSubroutes() || $executionPriority) {
                    // prepare the final parameter by merging the passed parameter, with result parameter.
                    $params = array_merge($passedParameters, $result['parameter']);

                    return array(
                        'route' => $result['route'],
                        'parameter' => $params,
                        'continue' => $result['continue']);
                } else {
                    // if has passed parameter.
                    $passedParameters = array_merge(count($result['parameter']) > 0 ? $result['parameter'] : array(), $passedParameters);

                    $subrouteResult = $route->getSubroutes()->findRouteByRequest($request, $remainingPath, $passedParameters);

                    // if found. else. continue on this group.
                    if ($subrouteResult['route'] != false)
                        return $subrouteResult;
                }
            }
        }

        if ($this->failRoute)
            return array(
                'route' => $this->findRoute($this->failRoute),
                'parameter' => array('request' => $request),
                'continue' => false
            );

        // false default.
        return array(
            'route' => false,
            'parameter' => array(),
            'continue' => false
        );
    }

    /**
     * Recusively get the uppermost Routing\Group
     * @return Group $group
     */
    public function getRootGroup()
    {
        if (!$this->route)
            return $this;

        $group = $this->route->getGroup();

        return $group->getRootGroup();
    }

    /**
     * Whether this is a root group
     * @return bool
     */
    public function isRoot()
    {
        return !$this->route;
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
     * An array access get to conveniently
     * create an empty route with the optional name
     * @param string $name
     * @return Route
     */
    public function offsetGet($name)
    {
        if (isset($this->routes[$name]))
            return $this->routes[$name];

        $route = $this->factory->createRoute($this, $name, array());

        $this->addRoute($route);

        return $this->routes[$name] = $route;
    }

    public function offsetSet($offset, $value)
    {
        throw new Exception('offsetSet operation not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new Exception('OffsetUnset not allowed.');
    }

    /**
     * Create a route by given methods
     * @param string|array method
     * @param string $path
     * @return \Exedra\Routing\Route
     */
    public function method($method = null, $path = '/')
    {
        $parameters = array();

        $parameters['path'] = $path;

        if ($method)
            $parameters['method'] = $method;

        $route = $this->factory->createRoute($this, null, $parameters);

        $this->addRoute($route);

        return $route;
    }

    public function get($path = '/')
    {
        return $this->method('get', $path);
    }

    public function post($path = '/')
    {
        return $this->method('post', $path);
    }

    public function put($path = '/')
    {
        return $this->method('put', $path);
    }

    public function delete($path = '/')
    {
        return $this->method('delete', $path);
    }

    public function patch($path = '/')
    {
        return $this->method('patch', $path);
    }

    public function options($path = '/')
    {
        return $this->method('option', $path);
    }

    public function any($path = '/')
    {
        return $this->method(null, $path);
    }

    public function path($path = '/')
    {
        return $this->method(null, $path);
    }

    /**
     * @param string $uri
     * @return Route
     */
    public function uri($uri)
    {
        $route = $this->factory->createRoute($this, null, []);

        $this->addRoute($route);

        return $route->setUri($uri);
    }

    /**
     * @param string $domain
     * @return Route
     */
    public function domain($domain)
    {
        $route = $this->factory->createRoute($this, null, []);

        $this->addRoute($route);

        return $route->setDomain($domain);
    }

    /**
     * Create an empty route with the given tag
     * @param string $tag
     * @return Route
     */
    public function tag($tag)
    {
        $route = $this->factory->createRoute($this, null, array());

        $this->addRoute($route);

        return $route->tag($tag);
    }

    /**
     * Set alias for given route
     *
     * @param string $routeName
     * @param string $alias
     * @return $this
     */
    public function addRouteAlias($routeName, $alias)
    {
        if (is_array($alias))
            foreach ($alias as $item)
                $this->aliasIndices[$item] = $routeName;
        else
            $this->aliasIndices[$alias] = $routeName;

        return $this;
    }

    /**
     * Set alias for given group
     *
     * @param string $groupName
     * @param string|array $alias
     * @return $this
     */
    public function addGroupAlias($groupName, $alias)
    {
        if (is_array($alias))
            foreach ($alias as $item)
                $this->groupAliasIndices[$item] = $groupName;
        else
            $this->groupAliasIndices[$alias] = $groupName;

        return $this;
    }
}