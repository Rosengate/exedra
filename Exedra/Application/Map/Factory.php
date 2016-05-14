<?php
namespace Exedra\Application\Map;

/**
 * A factory that handle the route/level/finding and HTTP request creation
 * This instance is injected into each level created
 */

class Factory
{
	/**
	 * Loader for lazy loading functionality
	 */
	protected $loader;

	/**
	 * Application instance
	 * @var \Exedra\Application app
	 */
	protected $app;

	protected $registry = array();

	protected $reflections = array();

	/**
	 * Routing path
	 * @param \Exedra\Path
	 */
	protected $path;

	/**
	 * Define explicitness of the routing.
	 * @var bool
	 */
	protected $isExplicit = false;

	public function __construct(\Exedra\Application $app)
	{
		$this->app = $app;

		$this->registerRoutingComponents();
	}

	/**
	 * Get routes base path
	 * @return \Exedra\Path
	 */
	public function getRoutesPath()
	{
		if(!$this->path)
		{
			if($this->app->path->hasRegistry('routes'))
				$this->path = $this->app->path['routes'];
			else
				$this->path = $this->app->path->register('routes', $this->app->path['app'].'/Routes', true);
		}

		return $this->path;
	}

	/**
	 * Get application instance
	 * @return \Exedra\Application
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * Get middleware registry from \Exedra\Application
	 * @return \Exedra\Application\Middleware\Registry
	 */
	public function getMiddlewareRegistry()
	{
		return $this->app->getMiddlewareRegistry();
	}

	public function isExplicit()
	{
		return $this->isExplicit;
	}

	/**
	 * Register basic components [Finding, ServerRequest, Exception]
	 */
	protected function registerRoutingComponents()
	{
		$this->register(array(
			'finding' => '\Exedra\Application\Map\Finding',
			'route' => '\Exedra\Application\Map\Route',
			'level' => '\Exedra\Application\Map\Convenient',
			'request' => '\Exedra\Http\ServerRequest',
			'exception' => '\Exception'
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
	 * @param \Exedra\Application\Map\Level level of where the route is based on
	 * @param string route name
	 * @param array parameters route parameter
	 * @return \Exedra\Application\Map\Route
	 */
	public function createRoute(Level $level, $name, array $parameters)
	{
		return $this->create('route', array($level, $name, $parameters));
	}

	/**
	 * Create level object
	 * @param \Exedra\Application\Map\Route route of where the level is based on
	 * @param array of routes
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevel(array $routes = array(), Route $route = null)
	{
		return $this->create('level', array($this, $route, $routes));
	}

	/**
	 * Create level by given path
	 * For now, assume the passed pattern as path
	 * @param \Exedra\Application\Map\Route
	 * @param string pattern
	 * @return \Exedra\Application\Map\Level
	 */
	public function createLevelFromString($path, $route = null)
	{
		$path = $this->getRoutesPath()->to($path);

		if(!file_exists($path))
			throw new \Exedra\Exception\NotFoundException('File ['.$path.'] does not exists.');
		
		$closure = require_once $path;

		// expecting a \Closure from this loaded file.
		if(!($closure instanceof \Closure))
			return $this->throwException('Failed to create a route level. The path ['.$path.'] must return a \Closure.');

		$level = $this->create('level', array($this, $route));

		$closure($level);

		return $level;
	}

	/**
	 * Create finding object
	 * @param \Exedra\Application\Map\Route result's route.
	 * @param array parameters
	 * @param \Exedra\Http\Request
	 * @return \Exedra\Application\Map\Finding
	 */
	public function createFinding(Route $route = null, array $parameters = null, \Exedra\Http\ServerRequest $request = null)
	{
		return $this->create('finding', array($route, $parameters, $request, $this->app->config));
	}

	public function throwException($message)
	{
		throw $this->create('exception', array($message));
	}
}