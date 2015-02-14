<?php
namespace Exedra\Application\Map;

class Finding
{
	/**
	 * \Exedra\Application\Map\Route route
	 */
	public $route;

	/**
	 * array middlewares
	 */
	public $middlewares = array();

	/**
	 * array parameters
	 */
	public $parameters = array();

	/**
	 * string of subapplication name.
	 */
	protected $subapp = null;

	/**
	 * \Exedra\Application\Config Configs
	 */
	public $configs;

	/**
	 * @param \Exedra\Application\Map\Route or null
	 * @param array parameters
	 */
	public function __construct(\Exedra\Application\Map\Route $route = null, array $parameters = array())
	{
		$this->route = $route;

		if($route)
		{
			$this->addParameter($parameters);
			// $this->parameters = $parameters;
			$this->configs = new \Exedra\Application\Config;
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
	 * @return boolean, whether this finding is success or not.
	 */
	public function success()
	{
		return $this->route ? true : false;
	}

	public function resolve()
	{
		$this->subapp = null;

		foreach($this->route->getFullRoutes() as $r)
		{
			// get the latest subapp.
			$this->subapp = $r->hasParameter('subapp') ? $r->getParameter('subapp') : $this->subapp;

			// has middleware.
			if($r->hasParameter('middleware'))
			{
				$this->middlewares[$r->getName()] = &$r->getParameter('middleware');
			}

			// pass conig.
			if($r->hasParameter('config'))
			{
				$this->configs->set($r->getParameter('config'));
			}
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
	 * Get a referenced config bag of this finding
	 * @return \Exedra\Application\Config
	 */
	public function &getConfig()
	{
		return $this->configs;
	}

	/**
	 * @return string referenced subapp
	 */
	public function &getSubapp()
	{
		return $this->subapp;
	}

	/**
	 * @return array referenced parameters
	 */
	public function &getParameter()
	{
		return $this->parameters;
	}
}


?>