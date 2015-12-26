<?php
namespace Exedra\Application\Map;

class Finding
{
	/**
	 * @var \Exedra\Application\Map\Route route
	 */
	public $route;

	/**
	 * @var array middlewares
	 */
	public $middlewares = array();

	/**
	 * @var array parameters
	 */
	public $parameters = array();

	/**
	 * string of module name.
	 * @var string|null
	 */
	protected $module = null;

	/**
	 * string of route base
	 * @var string|null
	 */
	protected $baseRoute = null;

	/**
	 * Request instance
	 * @var \Exedra\HTTP\Request|null
	 */
	public $request = null;

	/**
	 * @var \Exedra\Application\Config Configs
	 */
	public $configs;

	/**
	 * @param \Exedra\Application\Map\Route or null
	 * @param array parameters
	 */
	public function __construct(\Exedra\Application\Map\Route $route = null, array $parameters = array(), \Exedra\HTTP\Request $request = null, \Exedra\Application\Config $config)
	{
		$this->route = $route;
		$this->request = $request;

		if($route)
		{
			$this->addParameter($parameters);
			// $this->parameters = $parameters;
			// $this->configs = new \Exedra\Application\Config;
			$this->configs = clone $config;
			$this->resolve();
		}
	}

	/**
	 * Append the given parameters.
	 */
	public function addParameter(array $parameters)
	{
		foreach($parameters as $key => $param)
		{
			$this->parameters[$key] = $param;
		}
	}

	/**
	 * Get findings parameter
	 * Return all if no argument passed
	 * @return array|mixed
	 */
	public function param($name = null)
	{
		if($name === null)
			return $this->parameters;

		return $this->parameters[$name];
	}

	/**
	 * @return boolean, whether this finding is success or not.
	 */
	public function success()
	{
		return $this->route ? true : false;
	}

	public function resolve()
	{
		$this->module = null;
		$this->baseRoute = null;

		foreach($this->route->getFullRoutes() as $route)
		{
			// get the latest module and route base
			if($route->hasProperty('module'))
				$this->module = $route->getProperty('module');

			// if has parameter base, and it's true, set base route to the current route.
			if($route->hasProperty('base') && $route->getProperty('base') === true)
				$this->baseRoute = $route->getAbsoluteName();

			// has middleware.
			if($route->hasProperty('middleware'))
			{
				foreach($route->getProperty('middleware') as $middleware)
					$this->middlewares[] = $middleware;

				// $this->middlewares[$route->getName()] = $route->getProperty('middleware');
			}

			// pass conig.
			if($route->hasProperty('config'))
				$this->configs->set($route->getProperty('config'));
		}
	}

	/**
	 * Check has middlewares or not
	 * @return boolean
	 */
	public function hasMiddlewares()
	{
		return count($this->middlewares) > 0;
	}

	/**
	 * @return array middlewares
	 */
	public function &getMiddlewares()
	{
		return $this->middlewares;
	}

	/**
	 * Check has configs
	 * @return boolean
	 */
	public function hasConfigs()
	{
		return count($this->configs) > 0;
	}

	/**
	 * Config bag of this finding
	 * @return \Exedra\Application\Config
	 */
	public function getConfig()
	{
		return $this->configs;
	}

	/**
	 * Module on this finding.
	 * @return string referenced module name
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Get base route configured for this Finding.
	 * @return string
	 */
	public function getBaseRoute()
	{
		return $this->baseRoute;
	}
}


?>