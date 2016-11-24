<?php
namespace Exedra\Routing;

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
	 * @param string $path
	 */
	protected $lookupPath;

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
			'finding' => '\Exedra\Routing\Finding',
			'route' => '\Exedra\Routing\Route',
			'level' => '\Exedra\Routing\Router'
			));

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
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	public function createLevelFromString($path, $route = null)
	{
		$path = $this->lookupPath.'/'.ltrim($path, '/\\');

		if(!file_exists($path))
			throw new \Exedra\Exception\NotFoundException('File ['.$path.'] does not exists.');

		$closure = require $path;

		// expecting a \Closure from this loaded file.
		if(!($closure instanceof \Closure))
			throw new \Exedra\Exception\InvalidArgumentException('Failed to create routing level. The path ['.$path.'] must return a \Closure.');

		$level = $this->create('level', array($this, $route));

		$closure($level);

		return $level;
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