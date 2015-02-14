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
	 * // commented, can retrieve from currentExe if got.
	 * Current executed route.
	 * @var \Exedra\Application\Map\Route
	 */
	// private $currentRoute = null;

	/**
	 * List of executions
	 * @var array of \Exedra\Application\Execution\Exec
	 */
	protected $executions = array();

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
		$this->loader = new \Exedra\Loader($this->getBaseDir(), $this->structure);

		$this->di = new \Exedra\Application\Dic(array(
			"request"=>$this->exedra->httpRequest,
			"response"=>$this->exedra->httpResponse,
			"map"=> function() use($app) {return new \Exedra\Application\Map\Map($app);},
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception"),
			'file'=> array('\Exedra\Application\Builder\File', array($this)),
			'exeRegistry'=> array('\Exedra\Application\Registry', array($this))
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
	 * Return base directory for this app
	 * @return string.
	 */
	public function getBaseDir()
	{
		return $this->exedra->getBaseDir().'/'.$this->getAppName();
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
	 * Get last exec instance if have.
	 * @return \Exedra\Application\Execution\Exec
	 */
	public function getLastExecution()
	{
		if(($totalExec = count($this->executions)) == 0)
			return null;

		return $this->executions[$totalExec - 1];
	}

	/**
	 * Execute application
	 * @param mixed query
	 * @param array parameter
	 * @return mixed
	 */
	public function execute($query, $parameter = Array())
	{
		try
		{
			if(is_string($query))
			{
				// $route = $this->map->findByName($query);
				$finding = $this->map->findByName($query, $parameter);
			}
			else
			{
				// $result = $this->map->find($query);
				$finding = $this->map->find($query);
				$finding->addParameter($parameter);
			}

			// $route = $finding->route;

			// $parameter = count($result['parameter']) ? array_merge($parameter, $result['parameter']) : $parameter;
			// $parameter = count($finding->parameters) ? array_merge($parameter, $finding->parameters) : $parameter;

			// route not found.
			if(!$finding->success())
				return $this->throwFailedExecution($query, $parameter);

			// $route = $finding->route;
			// $this->currentRoute = $route;

			$exe = new Execution\Exec($this, $finding);

			// save to the stack of execution.
			$this->executions[] = $exe;

			// echo $this->exe->route->getAbsoluteName()."<br>";

			$execution = $finding->route->getParameter('execute');
			$execution = $this->exeRegistry->pattern->resolve($execution);

			// execute the stacked middleware.
			if($exe->middlewares->count() > 0)
			{
				// $exe->middlewares = new \ArrayIterator($this->registry->getMiddlewares());
				$exe->middlewares->resolve($exe);

				// set the last of the container as execution.
				$exe->middlewares->offsetSet($exe->middlewares->count(), $execution);

				// and reset.
				$exe->middlewares->rewind();

				// execute.
				$execution = $exe->middlewares->current();
			}

			$response = Execution\Resolver::resolve($execution($exe));

			// $executor	= new Execution\Executor($this->exeRegistry, $exe);
			// $execution	= $executor->execute($route->getParameter('execute'));

			// clear flash on every application execution (only if it has started).
			if(\Exedra\Application\Session\Session::hasStarted())
				$exe->flash->clear();
			
			return $response;
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
				return "<pre><hr><u>Execution Exception :</u>\n".$e->getMessage()."<hr>";
			}
		}
	}

	/**
	 * Throw failed execution if no route was found.
	 * @param mixed query
	 * @param array parameter
	 * @throws \Exception.
	 */
	protected function throwFailedExecution($query, array $parameter = array())
	{
		// prepare message.
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
}
?>