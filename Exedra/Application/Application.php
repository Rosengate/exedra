<?php
namespace Exedra\Application;

class Application
{
	private $started			= false;
	private $name				= null;
	private $executor			= null;
	public $router				= null;
	public $response			= null;
	public $structure			= null;
	private $executionFailRoute	= null;
	private $currentRoute		= null;
	private $currentExe 		= null;

	public function __construct($name,$exedra)
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
	 * Alias for top method.
	 */
	public function setFailRoute($routename)
	{
		$this->setExecutionFailRoute($routename);
	}

	public function register()
	{
		$app = $this;

		$this->structure = new \Exedra\Application\Structure\Structure($this->name);
		$this->loader = new \Exedra\Application\Structure\Loader($this->structure);

		$this->di = new \Exedra\Application\DI(array(
			"request"=>$this->exedra->httpRequest,
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

	public function getAppName()
	{
		return $this->name;
	}

	## return current execution result.
	public function getResult()
	{
		return $this->currentResult;
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
			$query	= !is_array($query) && is_string($query) ?Array("route"=>$query):$query;
			$result	= $this->map->find($query);

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
			$executor	= new Execution\Executor($this->controller,new Execution\Binder($binds),$this);
			$execution	= $executor->execute($route[$routename]['execute'],$exe);

			// clear flash on every application execution (only if it has started).
			if(\Exedra\Application\Session\Session::hasStarted())
				$this->exe->flash->clear();
			
			return $execution;
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
				$routeName	= $this->currentRoute['name'];
				return "<pre><hr><u>Execution Exception :</u>\n".$e->getMessage()."<hr>";
			}
		}
	}
}
?>