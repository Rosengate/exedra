<?php
namespace Exedra\Application\Execution;

class Exec
{
	/* application instance */
	public $app;

	/* route information */
	public $route;
	public $params	= Array();
	private $routePrefix = null;

	/* pointer each time  */
	// private $middlewarePointer	= 1;

	/* registered objects */
	private $registered	= Array();

	/* sub application */
	private $subapp;

	/* di container */
	public $di;

	public $config;

	public function __construct(\Exedra\Application\Map\Route $route, $app, $params, $config, $subapp = null)
	{
		$this->route = $route;
		$this->app = $app;
		$this->subapp = $subapp;
		$this->config = $config;

		## Create params
		foreach($params as $key=>$val)
		{
			$this->params[$key]	= $val;
		}

		$this->di = new \Exedra\Application\Dic(array(
			"loader"=> array("\Exedra\Loader", array($app->getExedra()->getBaseDir().'/'.$app->getAppName().'/'.$subapp, $this->app->structure)),
			"controller"=> array("\Exedra\Application\Builder\Controller", array($this)),
			"view"=> array("\Exedra\Application\Builder\View", array($this)),
			"middleware"=> array("\Exedra\Application\Builder\Middleware", array($this)),
			"url"=> array("\Exedra\Application\Builder\Url", array($this->app,$this)),
			"request"=>$this->app->request,
			"response"=>$this->app->exedra->httpResponse,
			"validator"=> array("\Exedra\Application\Utilities\Validator"),
			"flash"=> function() use($app) {return new \Exedra\Application\Session\Flash($app->session);},
			"redirect"=> array("\Exedra\Application\Response\Redirect", array($this)),
			"exception"=> array("\Exedra\Application\Builder\Exception", array($this)),
			"form"=> array("\Exedra\Application\Utilities\Form", array($this)),
			"session"=> function() use($app) {return $app->session;},
			"file"=> array("\Exedra\Application\Builder\File", array($app, $this->subapp))
			));
	}

	public function getSubapp()
	{
		return $this->subapp;
	}

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
		return call_user_func_array($this->middlewares->current(), func_get_args());
	}

	/**
	 * Get execution parameter(s)
	 * @param string name
	 * @return value
	 */
	public function param($name = null)
	{
		if(!$name) return $this->params;

		$params	= is_array($name)?$name:explode(",",$name);

		if(count($params) > 1)
		{
			$new	= Array();
			foreach($params as $k)
			{
				$new[] = $this->params[$k];
			}

			return $new;
		}
		else
		{
			return isset($this->params[$params[0]]) ? $this->params[$params[0]] : null;
		}
	}

	public function getParams()
	{
		return $this->params;
	}

	/**
	 * absolute route substracted by prefix, return absoluteRoute if passed true.
	 * @param boolean absolute, if true. will directly return absolute route.
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


	public function addParameter($key,$val = null)
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
	}

	/**
	 * check whether this exec has subapp
	 * @return boolean flag
	 */
	public function hasSubapp()
	{
		return $this->subapp === null ? false : true;
	}

	public function execute($route,$parameter = array())
	{
		$route = $this->prefixRoute($route);
		return $this->app->execute($route, $parameter);
	}
}