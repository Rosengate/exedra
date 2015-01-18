<?php namespace Exedra\Application;

class Application
{
	/**
	 * Application name. Reflected as your directory name.
	 * @var string
	 */
	private $name = null;

	/**
	 * Application structure
	 * @var \Exedra\Application\Structure
	 */
	public $structure = null;

	/**
	 * Application based loader.
	 * @var \Exedra\Application\Loader
	 */
	public $loader = null;

	/**
	 * Route for general exception handling.
	 * @var string
	 */
	private $executionFailRoute	= null;

	/**
	 * Current executed route.
	 * @var \Exedra\Application\Map\Route
	 */
	private $currentRoute = null;

	/**
	 * Current exec instance.
	 * @var \Exedra\Application\Execution\Exec
	 */
	private $exe = null;

	/**
	 * Create a new application
	 * @param string name (application name)
	 * @param \Exedra\Exedra exedra instance
	 */
	public function __construct($name, $exedra)
	{
		$this->name = $name;
		$this->exedra = $exedra;

		## register dependency.
		$this->register();
	}

	/**
	 * Set route for execution exception
	 * @param string routename
	 */
	public function setExecutionFailRoute($routename)
	{
		$this->executionFailRoute	= $routename;
	}

	/**
	 * Alias for above method.
	 * @param string routename
	 */
	public function setFailRoute($routename)
	{
		$this->setExecutionFailRoute($routename);
	}

	/**
	 * Register dependencies.
	 */
	private function register()
	{
		$app = $this;

		$this->structure = new \Exedra\Application\Structure\Structure($this->name);
		$this->loader = new \Exedra\Application\Structure\Loader($this->structure);

		$this->di = new \Exedra\Application\DI(array(
			"request"=>$this->exedra->httpRequest,
			"response"=>$this->exedra->httpResponse,
			"map"=> function() use($app) {return new \Exedra\Application\Map\Map($app);},
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception"),
			'file'=> array('\Exedra\Application\Builder\File', array($this))
			));
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
	 * Get application name
	 * @return string application name
	 */
	public function getAppName()
	{
		return $this->name;
	}

	/**
	 * Get exedra instance
	 * @return \Exedra\Exedra
	 */
	public function getExedra()
	{
		return $this->exedra;
	}

	/**
	 * Execute application
	 * @param mixed query
	 * @param array parameter
	 * @return mixed
	 */
	public function execute($query,$parameter = Array())
	{
		try
		{
			if(is_string($query))
			{
				$route = $this->map->findByName($query);
			}
			else
			{
				$result = $this->map->find($query);
				$route = $result['route'];

				$parameter = count($result['parameter']) ? array_merge($parameter, $result['parameter']) : $parameter;
			}

			// route not found.
			if(!$route)
			{
				if(is_array($query))
				{
					$q	= Array();
					foreach($query as $k=>$v)
						$q[] = $k.' : '.$v;

					$msg = 'Query :<br>'.implode("<br>",$q);
				}
				else
				{
					$msg = 'Route : '.$query;
				}

				return $this->exception->create('Route not found. '.$msg);
			}

			$this->currentRoute = $route;
			$subapp = null;
			$binds = array();
			$config = new \Exedra\Application\Config;

			// loop all the related route. initiate subapp, bind and config.
			foreach($route->getFullRoutes() as $route)
			{
				$subapp = $route->hasParameter('subapp') ? $route->getParameter('subapp') : $subapp;

				// has middleware.
				if($route->hasParameter('bind:middleware'))
				{
					$binds['middleware'][] = $route->getParameter('bind:middleware');
				}

				// initialize config
				if($route->hasParameter('config'))
				{
					foreach($route->getParameter('config') as $key=>$val)
						$config->set($key, $val);
				}
			}

			// Prepare result parameter and automatically create controller and view builder.
			$exe	= new Execution\Exec($route, $this, $parameter, $config, $subapp);

			$this->exe	= $exe;
			$executor	= new Execution\Executor(new Execution\Binder($binds), $this->loader);
			$execution	= $executor->execute($route->getParameter('execute'),$exe);

			// clear flash on every application execution (only if it has started).
			if(\Exedra\Application\Session\Session::hasStarted())
				$this->exe->flash->clear();
			
			return $execution;
			/*
			$query	= !is_array($query) && is_string($query) ?Array("route"=>$query):$query;

			$result	= $this->map->finda($query);

			echo "<pre>Original<br>";
			print_r($result);die;

			if(!$result)
			{
				$q	= Array();
				foreach($query as $k=>$v) $q[]	= $k." : ".$v;

				return $this->exception->create("Route not found. Query :<br>".implode("<br>",$q));
			}

			$route		= $result['route'];
			$routename	= $result['name'];

			$parameter	= array_merge($result['parameters'],$parameter);

			// save current route result.
			$this->currentRoute	= &$result;	

			$subapp = null;
			$binds = Array();
			$config	= new \Exedra\Application\Config;

			foreach($route as $routeName=>$routeData)
			{
				// Sub app
				$subapp	= isset($routeData['subapp'])?$routeData['subapp']:$subapp;

				// Binds
				if(isset($this->map->binds[$routeName]))
				{
					foreach($this->map->binds[$routeName] as $bindName=>$callback)
					{
						$binds[$bindName][]	= $callback;
					}
				}

				// Initialize config
				if(isset($this->map->config[$routeName]))
				{
					foreach($this->map->config[$routeName] as $paramName=>$val)
					{
						$config->set($paramName, $val);
					}
				}
			}

			// Prepare result parameter and automatically create controller and view builder.
			$exe	= new Execution\Exec($routename, $this, $parameter, $config, $subapp);

			$this->exe	= $exe;
			$executor	= new Execution\Executor(new Execution\Binder($binds), $this->loader);
			$execution	= $executor->execute($route[$routename]['execute'],$exe);

			// clear flash on every application execution (only if it has started).
			if(\Exedra\Application\Session\Session::hasStarted())
				$this->exe->flash->clear();
			
			return $execution;*/
		}
		catch(\Exception $e)
		{
			if($this->executionFailRoute)
			{
				$failRoute = $this->executionFailRoute;

				// set this false, so that it wont loop if later this fail route doesn't exists.
				$this->executionFailRoute = false;
				return $this->execute($failRoute,Array("exception"=>$e));
			}
			else
			{
				$routeName	= $this->currentRoute->getAbsoluteName();
				return "<pre><hr><u>Execution Exception :</u>\n".$e->getMessage()."<hr>";
			}
		}
	}
}
?>