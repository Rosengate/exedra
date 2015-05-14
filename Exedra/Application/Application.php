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
	 * Route for general exception handling.
	 * @var string
	 */
	private $executionFailRoute	= null;

	/**
	 * // commented, can retrieve from currentExe if got.
	 * Current executed route.
	 * @var \Exedra\Application\Map\Route
	 */

	/**
	 * List of executions
	 * @var array of \Exedra\Application\Execution\Exec
	 */
	protected $executions = array();

	/**
	 * Dependency injection container
	 * @var \Exedra\Application\Dic
	 */
	public $di;

	/**
	 * Create a new application
	 * @param string name (application name)
	 * @param \Exedra\Exedra exedra instance
	 */
	public function __construct($name, \Exedra\Exedra $exedra)
	{
		// initiate application name and save \Exedra\Exedra reference.
		$this->name = $name;
		$this->exedra = $exedra;

		// create application structure
		$this->structure = new \Exedra\Application\Structure\Structure();

		// register dependency.
		$this->register();
	}

	/**
	 * Register route for execution exception
	 * @param string routename
	 */
	public function setExecutionFailRoute($routename)
	{
		$this->executionFailRoute = $routename;
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
	protected function register()
	{
		$app = $this;

		$this->di = new \Exedra\Application\Dic(array(
			"loader"=> array("\Exedra\Loader", array($this->getBaseDir(), $this->structure)),
			"request"=>$this->exedra->httpRequest,
			"response"=>$this->exedra->httpResponse,
			"map"=> function() use($app) { return new \Exedra\Application\Map\Map(new \Exedra\Application\Map\Factory($app->loader));},
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception"),
			'file'=> function() use($app) { return new \Exedra\Application\Builder\File($app->loader);},
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
	public function execute($query, array $parameter = array())
	{
		try
		{
			// expect it as route name
			if(is_string($query))
			{
				$finding = $this->map->findByName($query, $parameter);
			}
			// expect it either \Exedra\HTTP\Request or array
			else
			{
				if($query instanceof \Exedra\HTTP\Request)
				{
					$request = $query;
				}
				else
				{
					$data = $query;
					$query = array();
					\Exedra\Functions\Arrays::initiateByNotation($query, $data);
					
					$request = new \Exedra\HTTP\Request($query);
				}

				$finding = $this->map->find($request);
				$finding->addParameter($parameter);
			}

			// route not found.
			if(!$finding->success())
				return $this->throwFailedExecution($query, $parameter);

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
				// simple customer error msg.
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