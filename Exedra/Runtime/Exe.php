<?php
namespace Exedra\Runtime;

class Exe extends \Exedra\Container\Container
{
	/**
	 * Array of (referenced) parameters for this execution.
	 * @var array
	 */
	protected $params = array();

	/**
	 * Base route to be appended on every execution scope based functionality.
	 * @var string
	 */
	protected $baseRoute = null;

	/**
	 * stack of callables
	 * @var array callStack
	 */
	protected $callStack;

	/**
	 * Middleware registry of application instance
	 * @var \Exedra\Application\Middleware\Registry
	 */
	protected $middlewareRegistry;

	/**
	 * Route for handling exception
	 * @var string
	 */
	protected $failRoute = null;

	public function __construct(
		\Exedra\Application $app,
		\Exedra\Middleware\Registry $middlewareRegistry,
		\Exedra\Routing\Finding $finding)
	{
		parent::__construct();

		$this->finding = $finding;

		$this->app = $app;

		$this->middlewareRegistry = $middlewareRegistry;

		// initiate services
		$this->initializeServices();

		// initiate service registry
		$this->setUp();

		// Initiate execution handles/middlewares
		$this->handle();
	}	

	/**
	 * Initialize execution services
	 */
	protected function initializeServices()
	{
		// Initiate loader, registry, route, config, params, and set base route based on finding.
		$this->services['path'] = $this->app->path;

		$this->services['route'] = $this->finding->getRoute();
		
		$this->services['config'] = clone $this->app->config;

		$this->services['config']->set($this->finding->getConfig());
		
		$this->setBaseRoute($this->finding->getBaseRoute());
		
		\Exedra\Support\DotArray::initialize($this->params, $this->finding->param());
		
		$this->services['request'] = $this->finding->getRequest();
		
		$this->services['response'] = \Exedra\Runtime\Response::createEmptyResponse();
	}

	/**
	 * Setup dependency registry
	 */
	protected function setUp()
	{
		$this->services['service']->register(array(
			'view' => function(){ return $this->module['Application']->view;},
			'controller' => function(){ return $this->module['Application']->controller;},
			'url' => function(){ return new \Exedra\Runtime\Factory\Url($this->app->map, $this->request, $this->config->get('app.url', null), $this->config->get('asset.url', null), $this);},
			'redirect' => array("\Exedra\Runtime\Redirect", array('self')),
			'form' => array("\Exedra\Runtime\Factory\Form", array('self')),
			// thinking of deprecating the asset as service
			'asset' => function(){ return new \Exedra\Factory\Asset($this->url, $this->app->path['public'], $this->config->get('asset', array()));}
			));
	}

	/**
	 * Initialize call stacks
	 * Handle application execution
	 * Set response body
	 */
	protected function handle()
	{
		$this->callStack = $this->finding->getMiddlewares();

		// resolve registry
		$this->middlewareRegistry->resolve($this, $this->callStack);

		$resolver = $this->app->create('handler.resolver');

		// add final handle
		$this->callStack[] = $resolver->resolve($this, $this->route->getProperty('execute'), $this->finding->getHandlers());

		// first handle.
		$handle = reset($this->callStack);

		// execute.
		$this->response->setBody($handle($this));
	}

	/**
	 * Get module path
	 * @param string path
	 * @return \Exedra\Path
	 */
	public function getModulePath($path = null)
	{
		$path = $path ? ltrim($path, '/\\') : '';

		$base = $this->path['app'];

		return ($module = $this->getModule()) ? $base->create($module.'/'.$path) : $base->create($path);
	}

	/**
	 * Get execution namespace
	 * Module appended namespace
	 * @return string
	 */
	public function getNamespace($namespace = null)
	{
		return $this->app->getNamespace($module = $this->getModule() ? '\\' . $module : '') . ($namespace ? '\\'.$namespace : '');
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
	 * Get response instance
	 * @return \Exedra\Runtime\Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Get base dir for this execution instance. A concenated app base directory and this module.
	 * @return string.
	 */
	public function getBaseDir()
	{
		return rtrim($this->app->getBaseDir(), '/'). '/' . $this->getModule();
	}

	/**
	 * Set route for handling exception
	 * @var string route
	 */
	public function setFailRoute($route)
	{
		$this->failRoute = $route;
	}

	/**
	 * Get route for handling exception
	 * @return string
	 */
	public function getFailRoute()
	{
		return $this->failRoute;
	}

	/**
	 * Get execution finding
	 * @return string
	 */
	public function getFinding()
	{
		return $this->finding;
	}

	/**
	 * Point and execute the next handler
	 * @return mixed
	 */
	public function next()
	{
		return call_user_func_array(next($this->callStack), func_get_args());
	}

	/**
	 * Validate against given parameters
	 * @param array params
	 * @return boolean
	 */
	public function isParams(array $params)
	{
		foreach($params as $key => $value)
			if($this->param($key) != $value)
				return false;

		return true;
	}

	/**
	 * Check if the given route exists within the current route.
	 * @param string route
	 * @param array params (optional)
	 * @return boolean
	 */
	public function hasRoute($route, array $params = array())
	{
		if(strpos($route, '@') === 0)
			$isRoute = strpos($this->getAbsoluteRoute(), substr($route, 1)) === 0;
		else
			$isRoute = strpos($this->getRoute(), $route) === 0;

		if(!$isRoute)
			return false;

		if(count($params) === 0)
			return true;

		return $this->isParams($params);
	}

	/**
	 * Check if the given route is equal
	 * @param string route
	 * @param array params (optional)
	 * @return boolean
	 */
	public function isRoute($route, array $params = array())
	{
		if(strpos($route, '@') === 0)
			$isRoute = $this->getAbsoluteRoute() == substr($route, 1);
		else
			$isRoute = $this->getRoute() == $route;

		if(!$isRoute)
			return false;

		if(count($params) === 0)
			return true;

		return $this->isParams($params);
	}

	/**
	 * Get execution parameter
	 * @param string name
	 * @param mixed default value (optional)
	 * @return mixed or default if not found.
	 */
	public function param($name, $default = null)
	{
		return \Exedra\Support\DotArray::has($this->params, $name) ? \Exedra\Support\DotArray::get($this->params, $name) : $default;
	}

	/**
	 * Get route meta information
	 * @param string key
	 */
	public function meta($key)
	{
		return $this->finding->getMeta($key);
	}

	/**
	 * Check whether given meta key exists
	 * @param string key
	 * @return bool
	 */
	public function hasMeta($key)
	{
		return $this->finding->hasMeta($key);
	}

	/**
	 * Check whether given param key exists
	 * @param string name
	 * @return boolean
	 */
	public function hasParam($name)
	{
		return \Exedra\Support\DotArray::has($this->params, $name);
	}

	/**
	 * Update the given param
	 * @param string key
	 * @param mixed value
	 * @return this
	 */
	public function setParam($key, $value = null)
	{
		\Exedra\Support\DotArray::set($this->params, $key, $value);
	}

	/**
	 * A public functionality to add parameter(s) to $exe.
	 * @param string name
	 * @param mixed value
	 * @return this;
	 */
	public function addParam($key, $value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
				$this->setParam($k, $v);
		}
		else
		{
			if(isset($this->params[$key]))
				throw new \InvalidArgumentException('The given key ['.$key.'] has already exist');
				
			$this->params[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get parameters by the given list of key
	 * @param array keys (optional)
	 * @return array
	 */
	public function params(array $keys = array())
	{
		if(count($keys) == 0)
			return $this->params;

		$params = array();

		foreach($keys as $key)
		{
			$key = trim($key);

			$params[$key] = isset($this->params[$key]) ? $this->params[$key] : null;
		}

		return $params;
	}

	/**
	 * Route name relative to the current base route, return absolute route if true boolean is given as argument.
	 * @param boolean absolute, if true. will directly return absolute route. The same use of getAbsoluteRoute
	 * @return string
	 */
	public function getRouteName($absolute = false)
	{
		if($absolute !== true)
		{
			$baseRoute = $this->getBaseRoute();
			$absoluteRoute = $this->getAbsoluteRoute();

			if(!$baseRoute) return $absoluteRoute;

			$route	= substr($absoluteRoute, strlen($baseRoute)+1, strlen($absoluteRoute));

			return $route;
		}
		else
		{
			return $this->route->getAbsoluteName();
		}
	}

	/**
	 * Alias to getRouteName()
	 * @param bool absolute
	 * @return string
	 */
	public function getRoute($absolute = false)
	{
		return $this->getRouteName($absolute);
	}

	/** 
	* get absolute route. 
	* @return current route absolute name.
	*/
	public function getAbsoluteRoute()
	{
		return $this->route->getAbsoluteName();
	}

	/**
	 * Get parent route. For example, route for public.main.index will return public.main.
	 * Used on getBaseRoute()
	 * @return string of parent route name.
	 */
	public function getParentRoute()
	{
		return $this->route->getParentRoute();
	}

	/**
	 * Set a base route for this execution
	 * @param string route
	 */
	public function setBaseRoute($route)
	{
		$this->baseRoute = $route;
	}

	/**
	 * Get base route for this execution
	 * @return string|null
	 */
	public function getBaseRoute()
	{
		if($this->baseRoute)
			$baseRoute	= $this->baseRoute;
		else
			$baseRoute	= $this->getParentRoute();

		return $baseRoute ? $baseRoute : null;
	}

	/**
	 * Base the given route.
	 * Or return an absolute route, if absolute character was given at the beginning of the given string.
	 * @param string route
	 */
	public function baseRoute($route)
	{
		if(strpos($route, '@') === 0)
		{
			$route = substr($route, 1, strlen($route)-1);
		}
		else
		{
			$baseRoute = $this->getBaseRoute();
			$route		= $baseRoute ? $baseRoute.'.'.$route : $route;
		}

		return $route;
	}

	/**
	 * Get module name.
	 * @return string
	 */
	public function getModule()
	{
		return $this->finding->getModule();
	}

	/**
	 * check whether this exec has module
	 * @return boolean flag
	 */
	public function hasModule()
	{
		return $this->getModule() === null ? false : true;
	}

	/**
	 * Forward current request to the given route
	 * @param string route
	 * @param array args
	 */
	public function forward($route, array $args = array())
	{
		$route = $this->baseRoute($route);

		return $this->app->execute($route, $args, $this->request);
	}

	/**
	 * Execute a scope based route
	 * @param string route
	 * @param array parameter.
	 */
	public function execute($route, array $parameters = array(), \Exedra\Http\ServerRequest $request = null)
	{
		$route = $this->baseRoute($route);

		$request = $request ? : $this->request;

		return $this->app->execute($route, $parameters, $request);
	}

	/**
	 * Alias to app->request()
	 * @param \Exedra\Http\ServerRequest request
	 * @return \Exedra\Runtime\Exe
	 */
	public function request(\Exedra\Http\ServerRequest $request)
	{
		return $this->app->request($request);
	}

	/**
	 * Retrieve the actual execution instance
	 * @return \Exedra\Runtime\Exe
	 */
	public function finalize()
	{
		$exe = $this;

		while(true)
		{
			$body = $exe->response->getBody();

			if($body instanceof \Exedra\Runtime\Exe)
				$exe = $body;
			else
				break;
		}

		return $exe;
	}

	/**
	 * Extended container::solve method
	 * for shared service/factory/callable check
	 * @param string type
	 * @param string name
	 * @param array args
	 * @return mixed
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	protected function solve($type, $name, array $args = array())
	{
		if(!$this->services[$type]->has($name))
		{
			if($this->app[$type]->has('@'.$name))
			{
				return $this->app->dependencyCall($type, $name, $args);
			}
			else
			{
				$isShared = false;

				if($type == 'callable' && ($this->app['service']->has($name) || $isShared = $this->app['service']->has('@'.$name)))
				{
					$service = $this->app->get($isShared ? '@'.$name : $name);

					$this->invokables[$name] = $service;

					if(is_callable($service))
						return call_user_func_array($service, $args);
				}

				throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Unable to find ['.$name.'] in the registered '.$type);
			}
		}
		else
		{
			$registry = $this->services[$type]->get($name);
		}

		return $this->filter($type, $name, $this->resolve($name, $registry, $args));
	}
}