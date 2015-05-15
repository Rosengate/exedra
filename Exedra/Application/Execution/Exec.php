<?php
namespace Exedra\Application\Execution;

class Exec
{
	/**
	 * Application instance
	 * @var \Exedra\Application\Application
	 */
	public $app;

	/**
	 * Route instance.
	 * @var \Exedra\Application\Map\Route
	 */
	public $route;

	/**
	 * Array of (referenced) parameters for this execution.
	 * @var array
	 */
	public $params	= Array();

	/**
	 * Route prefix to be appended on every execution scope based functionality.
	 * @var string
	 */
	private $routePrefix = null;

	/**
	 * Dependecy injection container
	 * @var \Exedra\Application\Dic
	 */
	public $di;

	/**
	 * Map finding result
	 * @var \Exedra\Application\Map\Finding
	 */
	public $finding;

	/**
	 * Execution config instance
	 * @var \Exedra\Application\Config
	 */
	public $config;

	public function __construct(\Exedra\Application\Application $app, \Exedra\Application\Map\Finding $finding)
	{
		$this->finding = $finding;
		$this->app = $app;

		// initiate properties
		$this->initiateProperties($app, $finding);

		// initiate dependencies
		$this->initiateContainer();

		// Initiate middlewares
		$this->initiateMiddlewares();
	}

	/**
	 * Initiate execution properties
	 */
	protected function initiateProperties()
	{
		// Initiate.
		$this->registry = $this->app->exeRegistry;
		$this->route = $this->finding->route;
		$this->config = &$this->finding->getConfig();
		$this->params = &$this->finding->getParameter();
	}

	/**
	 * Initiate dependency injection container
	 */
	protected function initiateContainer()
	{
		$app = $this->app;
		$exe = $this;

		$this->di = new \Exedra\Application\Dic(array(
			"loader"=> array("\Exedra\Loader", array($this->getBaseDir(), $this->app->structure)),
			"controller"=> array("\Exedra\Application\Builder\Controller", array($this)),
			"view"=> array("\Exedra\Application\Builder\View", array($this)),
			"middleware"=> array("\Exedra\Application\Builder\Middleware", array($this)),
			"url"=> array("\Exedra\Application\Builder\Url", array($this->app,$this)),
			"request"=>$this->finding->request ? : $this->app->request, // use finding based request if found, else, use the original http request one.
			"response"=>$this->app->exedra->httpResponse,
			"validator"=> array("\Exedra\Application\Utilities\Validator"),
			"flash"=> function() use($app) {return new \Exedra\Application\Session\Flash($app->session);},
			"redirect"=> array("\Exedra\Application\Response\Redirect", array($this)),
			"exception"=> array("\Exedra\Application\Builder\Exception", array($this)),
			"form"=> array("\Exedra\Application\Utilities\Form", array($this)),
			"session"=> function() use($app) {return $app->session;},
			"file"=> function() use($exe) {return new \Exedra\Application\Builder\File($exe->loader);},
			'middlewares'=> array('\Exedra\Application\Execution\Middlewares'),
			'asset' => array('\Exedra\Application\Builder\Asset', array($this))
			));
	}

	/**
	 * Get base dir for this execution instance. A concenated app base directory and this subapp.
	 * @return string.
	 */
	public function getBaseDir()
	{
		return rtrim($this->app->getBaseDir(), '/'). '/' . $this->getSubapp();
	}

	/**
	 * Initiate execution middlewares.
	 */
	protected function initiateMiddlewares()
	{
		// if there's middlewares in registry.
		if($this->registry->hasMiddlewares())
			$this->middlewares->addByArray($this->registry->getMiddlewares());

		// finding' middleware
		if($this->finding->hasMiddlewares())
			$this->middlewares->addByArray($this->finding->getMiddlewares());
	}

	/**
	 * Get subapplication name.
	 * @return string
	 */
	public function getSubapp()
	{
		return $this->finding->getSubapp();
	}

	/**
	 * Resolve dependency from dependency injection dependency injection container, off property $di.
	 * @return mixed.
	 */
	public function __get($property)
	{
		if($this->di->has($property))
		{
			$this->$property = $this->di->get($property);
			return $this->$property;
		}
	}

	/**
	 * Point to the next handler, and execute that handler.
	 */
	public function next()
	{
		// move to next middleware
		$this->middlewares->next();

		// and execute
		return call_user_func_array($this->middlewares->current(), func_get_args());
	}

	/**
	 * Get execution parameter
	 * @param string name
	 * @param mixed default value (optional)
	 * @return mixed or default if not found.
	 */
	public function param($name, $default = null)
	{
		if(!$name) return $this->params;

		return isset($this->params[$name]) ? $this->params[$name] : $default;
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
			$params[] = $this->params[trim($key)];

		return $params;
	}

	/**
	 * absolute route substracted prefix, return absoluteRoute if passed true.
	 * @param boolean absolute, if true. will directly return absolute route.
	 * @return string
	 */
	public function getRoute($absolute = false)
	{
		if(!$absolute)
		{
			$routePrefix = $this->getRoutePrefix();
			$absoluteRoute = $this->getAbsoluteRoute();

			if(!$routePrefix) return $absoluteRoute;

			$route	= substr($absoluteRoute, strlen($routePrefix)+1, strlen($absoluteRoute));

			return $route;
		}
		else
		{
			return $this->getAbsoluteRoute();
		}
	}

	/** 
	* get absolute route. 
	* @return current route absolute name.
	*/
	private function getAbsoluteRoute()
	{
		return $this->route->getAbsoluteName();
	}

	/**
	 * Get parent route. For example, route for public.main.index will return public.main.
	 * Used on getRoutePrefix()
	 */
	private function getParentRoute()
	{
		return $this->route->getParentRoute();
		$absoluteRoute	= $this->getAbsoluteRoute();
		$absoluteRoutes	= explode(".",$absoluteRoute);

		if(count($absoluteRoutes) == 1)
			return null;

		array_pop($absoluteRoutes);
		$routePrefix	= implode(".",$absoluteRoutes);
		return $routePrefix;
	}

	/**
	 * Set a route prefix for this execution.
	 * @param string prefix
	 */
	public function setRoutePrefix($prefix)
	{
		$this->routePrefix = $prefix;
	}

	/**
	 * Get a prefix for this execution. Return null, if not set.
	 * @return string prefix.
	 */
	public function getRoutePrefix()
	{
		if($this->routePrefix)
			$routePrefix	= $this->routePrefix;
		else
			$routePrefix	= $this->getParentRoute();

		return $routePrefix?$routePrefix:null;
	}

	/**
	 * Prefix the given route. Or return an absolute route, if absolute character was given at the beginning of the given string.
	 * @param string route
	 */
	public function prefixRoute($route)
	{
		if(strpos($route, $this->app->structure->getCharacter('absolute')) === 0)
		{
			$route = substr($route, 1, strlen($route)-1);
		}
		else
		{
			$routePrefix = $this->getRoutePrefix();
			$route		= $routePrefix?$routePrefix.".".$route:$route;
		}

		return $route;
	}

	/*public function addParameter($key,$val = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->addParameter($k,$v);
			}
		}
		else
		{
			## resolve the parameter.
			foreach($this->params as $k=>$v)
			{
				$key	= str_replace('{'.$k.'}',$v,$key);
				$val	= str_replace('{'.$k.'}', $v, $val);
			}

			// $this->params[$key]	= $val;

			## pointer.
			if(strpos($val, "&") === 0)
			{
				### create array by notation.
				$val	= str_replace("&","",$val);
				if(\Exedra\Functions\Arrays::hasByNotation($this->params,$val))
				{
					$ref	= \Exedra\Functions\Arrays::getByNotation($this->params,$val);
					\Exedra\Functions\Arrays::setByNotation($this->params,$key,$ref);
				}
			}
			else
			{
				\Exedra\Functions\Arrays::setByNotation($this->params,$key,$val);
			}
		}
	}*/

	/**
	 * check whether this exec has subapp
	 * @return boolean flag
	 */
	public function hasSubapp()
	{
		return $this->getSubapp() === null ? false : true;
	}

	/**
	 * Execute route but scope based route.
	 * @param string route
	 * @param array parameter.
	 */
	public function execute($route, array $parameter = array())
	{
		$route = $this->prefixRoute($route);
		return $this->app->execute($route, $parameter);
	}
}