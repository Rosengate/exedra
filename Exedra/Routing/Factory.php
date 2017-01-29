<?php
namespace Exedra\Routing;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routing\Handler\ArrayHandler;
use Exedra\Routing\Handler\ClosureHandler;
use Exedra\Routing\Handler\PathHandler;

/**
 * A factory that handle the route/level/finding creation
 * This instance is injected into each created level
 */
class Factory
{
	/**
	 * Application instance
	 * @var \Exedra\Application $app
	 */
	protected $app;

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
     * @var LevelHandler[] $levelHandlers
     */
    protected $levelHandlers = array();

    /**
     * @var LevelHandler[] $defaultHandlers
     */
    protected $defaultHandlers;

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
	 * Register basic components [finding, route, level]
	 */
	protected function setUp()
	{
		$this->register(array(
			'finding' => Finding::class,
			'route' => Route::class,
			'level' => Router::class
			));

        $this->addDefaultHandler(new ClosureHandler());
        $this->addDefaultHandler(new ArrayHandler());
        $this->addDefaultHandler(new PathHandler());

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
	 * @param \Exedra\Routing\Level $level of where the route is based on
	 * @param string $name
	 * @param array $parameters route parameter
	 * @return \Exedra\Routing\Route
	 */
	public function createRoute(Level $level, $name, array $parameters)
	{
		return $this->create('route', array($level, $name, $parameters));
	}

    /**
     * Create level object
     * @param array $routes
     * @param \Exedra\Routing\Route $route of where the level is based on
     * @return \Exedra\Routing\Level
     */
    public function createLevel(array $routes = array(), Route $route = null)
    {
        return $this->create('level', array($this, $route, $routes));
    }

    /**
     * Create level by given path
     * For now, assume the passed pattern as path
     * @param string $path
     * @param Route|null $route
     * @return \Exedra\Routing\Level
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function resolveLevel($pattern, $route = null)
    {
        foreach($this->levelHandlers as $handler)
        {
            if(!$handler->validate($pattern, $route))
                continue;

            return $handler->resolve($this, $pattern, $route);
        }

        foreach($this->defaultHandlers as $handler)
        {
            if(!$handler->validate($pattern, $route))
                continue;

            return $handler->resolve($this, $pattern, $route);
        }

        throw new InvalidArgumentException('Unable to resolve the routing group pattern');
    }

	public function addLevelHandler(LevelHandler $handler)
    {
        $this->levelHandlers[] = $handler;

        return $this;
    }

    protected function addDefaultHandler(LevelHandler $handler)
    {
        $this->defaultHandlers[] = $handler;

        return $this;
    }

	/**
	 * Create route finding
	 * @param \Exedra\Routing\Route|null
	 * @param array $parameters
	 * @param \Exedra\Http\ServerRequest $request
	 * @return \Exedra\Routing\Finding
	 */
	public function createFinding(Route $route = null, array $parameters = null, \Exedra\Http\ServerRequest $request = null)
	{
		return $this->create('finding', array($route, $parameters, $request));
	}
}