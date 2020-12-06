<?php

namespace Exedra\Routing;

use Exedra\Contracts\Routing\ExecuteHandler;
use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Contracts\Routing\RoutingHandler;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routing\GroupHandlers\ArrayHandler;
use Exedra\Routing\GroupHandlers\ClosureHandler;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A factory that handle the route/group/finding creation
 * This instance is injected into each created group
 */
class Factory
{
    /**
     * Classes registry
     * @var array $registry
     */
    protected $registry = array();

    /**
     * Reflection of classes on creating
     * @var array $reflections
     */
    protected $reflections = array();

    /**
     * Routes lookup path
     * Used when a string based subroutes is passed
     * @var string $path
     */
    protected $lookupPath;

    /**
     * @var GroupHandler[] $groupHandlers
     */
    protected $groupHandlers = array();

    /**
     * @var RoutingHandler[] $routingHandlers
     */
    protected $routingHandlers = array();

    /**
     * @var ExecuteHandler[] $executeHandlers
     */
    protected $executeHandlers = array();

    /**
     * @var GroupHandler[] $defaultGroupHandlers
     */
    protected $defaultGroupHandlers;

    public function __construct()
    {
        $this->setUp();
    }

    /**
     * Get routes lookup path
     * @return string
     */
    public function getLookupPath()
    {
        return $this->lookupPath;
    }

    /**
     * Register basic components [finding, route, group]
     */
    protected function setUp()
    {
        // to deprecate this method because we've no control over the dependencies
//        $this->register(array(
//            'finding' => Finding::class,
//            'route' => Route::class,
//            'group' => Group::class
//        ));

        $this->addDefaultGroupHandler(new ClosureHandler());
        $this->addDefaultGroupHandler(new ArrayHandler());
//        $this->addDefaultGroupHandler(new PathHandler());
        $this->addExecuteHandlers(new ExecuteHandlers\ClosureHandler());

        return $this;
    }

    /**
     * Register classname
     * @param array $registry
     * @return $this
     */
    public function register(array $registry)
    {
        foreach ($registry as $name => $classname) {
            $this->registry[$name] = $classname;

            unset($this->reflections[$name]);
        }

        return $this;
    }

    /**
     * General method to create classes from the registered list.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function create($name, array $arguments = array())
    {
        if (!isset($this->reflections[$name]))
            $this->reflections[$name] = new \ReflectionClass($this->registry[$name]);

        $reflection = $this->reflections[$name];

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Create route object
     * @param \Exedra\Routing\Group $group of where the route is based on
     * @param string $name
     * @param array $parameters route parameter
     * @return \Exedra\Routing\Route
     */
    public function createRoute(Group $group, $name, array $parameters)
    {
        if (!isset($this->registry[$name]))
            return new Route($group, $name, $parameters);

        return $this->create('route', array($group, $name, $parameters));
    }

    /**
     * Create routing group object
     * @param array $routes
     * @param \Exedra\Routing\Route $route of where the group is based on
     * @return \Exedra\Routing\Group
     */
    public function createGroup(array $routes = array(), Route $route = null)
    {
        if (!isset($this->registry['group']))
            return new Group($this, $route, $routes);

        return $this->create('group', array($this, $route, $routes));
    }

    /**
     * Create routing group by given path
     * For now, assume the passed pattern as path
     * @param string $pattern
     * @param Route|null $route
     * @return \Exedra\Routing\Group
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function resolveGroup($pattern, $route = null)
    {
        foreach ($this->groupHandlers as $handler) {
            if (!$handler->validateGroup($pattern, $route))
                continue;

            return $handler->resolveGroup($this, $pattern, $route);
        }

        foreach ($this->routingHandlers as $handler) {
            if (!$handler->validateGroup($pattern, $route))
                continue;

            return $handler->resolveGroup($this, $pattern, $route);
        }

        foreach ($this->defaultGroupHandlers as $handler) {
            if (!$handler->validateGroup($pattern, $route))
                continue;

            return $handler->resolveGroup($this, $pattern, $route);
        }

        throw new InvalidArgumentException('Unable to resolve the routing group pattern');
    }

    /**
     * Add routing GroupHandler
     * @param GroupHandler $handler
     * @return $this
     */
    public function addGroupHandler(GroupHandler $handler)
    {
        $this->groupHandlers[] = $handler;

        return $this;
    }

    /**
     * Add routing handler
     * @param RoutingHandler $handler
     * @return $this
     */
    public function addRoutingHandler(RoutingHandler $handler)
    {
        $this->routingHandlers[] = $handler;

        return $this;
    }

    /**
     * Add execute handler
     * @param ExecuteHandler $handler
     * @return $this
     */
    public function addExecuteHandlers(ExecuteHandler $handler)
    {
        $this->executeHandlers[] = $handler;

        return $this;
    }

    /**
     * Get execute handlers
     * @return ExecuteHandler[]
     */
    public function getExecuteHandlers()
    {
        return array_merge($this->executeHandlers, $this->routingHandlers);
    }

    /**
     * Add default group handler
     * This default is looked at the last order
     * @param GroupHandler $handler
     * @return $this
     */
    protected function addDefaultGroupHandler(GroupHandler $handler)
    {
        $this->defaultGroupHandlers[] = $handler;

        return $this;
    }

    /**
     * Create route finding
     * @param Route|null $route
     * @param array $parameters
     * @param ServerRequestInterface $request
     * @return \Exedra\Routing\Finding
     */
    public function createFinding(Route $route = null, array $parameters = null, ServerRequestInterface $request = null)
    {
        if (!isset($this->registry['finding']))
            return new Finding($route, $parameters, $request);

        return $this->create('finding', array($route, $parameters, $request));
    }
}