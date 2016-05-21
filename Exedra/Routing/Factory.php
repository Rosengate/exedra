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
	 * @var \Exedra\Application app
	 */
	protected $app;

	/**
	 * Classes registry
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * Reflection of classes on creating
	 * @var array reflections
	 */
	protected $reflections = array();

	/**
	 * Absolute path to the location of routes
	 * @param string path
	 */
	protected $path;

	public function __construct($path)
	{
		$this->path = rtrim($path, '/\\');

		$this->registerRoutingComponents();
	}

	/**
	 * Get routes base path
	 * Used when subroutes specified as string
	 * @return \Exedra\Path
	 */
	public function getRoutesPath()
	{
		return $this->path;
	}

	/**
	 * Register basic components [finding, route, level]
	 */
	protected function registerRoutingComponents()
	{
		$this->register(array(
			'finding' => '\Exedra\Routing\Finding',
			'route' => '\Exedra\Routing\Route',
			'level' => '\Exedra\Routing\Convenient'
			));

		return $this;
	}

	/**
	 * Register classname
	 * @param string name
	 * @param string classname
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
	 * @param string name
	 * @param array arguments
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
	 * @param \Exedra\Routing\Level level of where the route is based on
	 * @param string route name
	 * @param array parameters route parameter
	 * @return \Exedra\Routing\Route
	 */
	public function createRoute(Level $level, $name, array $parameters)
	{
		return $this->create('route', array($level, $name, $parameters));
	}

	/**
	 * Create level object
	 * @param \Exedra\Routing\Route route of where the level is based on
	 * @param array of routes
	 * @return \Exedra\Routing\Level
	 */
	public function createLevel(array $routes = array(), Route $route = null)
	{
		return $this->create('level', array($this, $route, $routes));
	}

	/**
	 * Create level by given path
	 * For now, assume the passed pattern as path
	 * @param \Exedra\Routing\Route
	 * @param string pattern
	 * @return \Exedra\Routing\Level
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	public function createLevelFromString($path, $route = null)
	{
		$path = $this->path.'/'.ltrim($path, '/\\');

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
	 * @param \Exedra\Routing\Route result's route.
	 * @param array parameters
	 * @param \Exedra\Http\ServerRequest
	 * @return \Exedra\Routing\Finding
	 */
	public function createFinding(Route $route = null, array $parameters = null, \Exedra\Http\ServerRequest $request = null)
	{
		return $this->create('finding', array($route, $parameters, $request));
	}
}