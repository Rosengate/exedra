<?php
namespace Exedra\Routing;
use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routing\Handler\ArrayHandler;
use Exedra\Routing\Handler\ClosureHandler;
use Exedra\Routing\Handler\PathHandler;
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
     * @var GroupHandler[] $defaultGroupHandlers
     */
    protected $defaultGroupHandlers;

    public function __construct($lookupPath)
	{
		$this->lookupPath = rtrim($lookupPath, '/\\');

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
		$this->register(array(
			'finding' => Finding::class,
			'route' => Route::class,
			'group' => Group::class
			));

        $this->addDefaultGroupHandler(new ClosureHandler());
        $this->addDefaultGroupHandler(new ArrayHandler());
        $this->addDefaultGroupHandler(new PathHandler());

		return $this;
	}

    /**
     * Register classname
     * @param array $registry
     * @return $this
     */
	public function register(array $registry)
	{
		foreach($registry as $name => $classname)
		{
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
		if(!isset($this->reflections[$name]))
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
        foreach($this->groupHandlers as $handler)
        {
            if(!$handler->validate($pattern, $route))
                continue;

            return $handler->resolve($this, $pattern, $route);
        }

        foreach($this->defaultGroupHandlers as $handler)
        {
            if(!$handler->validate($pattern, $route))
                continue;

            return $handler->resolve($this, $pattern, $route);
        }

        throw new InvalidArgumentException('Unable to resolve the routing group pattern');
    }

	public function addGroupHandler(GroupHandler $handler)
    {
        $this->groupHandlers[] = $handler;

        return $this;
    }

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
		return $this->create('finding', array($route, $parameters, $request));
	}
}