<?php namespace Exedra\Application;

class Application
{
	/**
	 * Application name. Reflected as your application directory name.
	 * @var string
	 */
	protected $name = null;

	/**
	 * Exedra
	 * @var \Exedra\Exedra
	 */
	public $exedra;

	/**
	 * Application structure
	 * @var \Exedra\Application\Structure
	 */
	public $structure = null;

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
	 * Current execution instance.
	 * @var \Exedra\Application\Execution\Exec
	 */
	protected $exe = null;

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

		// initialize properties
		$this->initiateProperties();

		// register dependencies.
		$this->initiateContainer();
	}

	/**
	 * Alias for above method.
	 * @param string routename
	 */
	public function setFailRoute($routename)
	{
		$this->registry->setFailRoute($routename);
	}

	protected function initiateProperties()
	{
		// create application structure and loader
		$this->structure = new \Exedra\Application\Structure\Structure();
		$this->loader = new \Exedra\Loader($this->getBaseDir(), $this->structure);
	}

	/**
	 * Register dependencies.
	 */
	protected function initiateContainer()
	{
		$app = $this;

		$this->di = new \Exedra\Application\Dic(array(
			'registry'=> array('\Exedra\Application\Registry', array($this)),
			"request"=>$this->exedra->httpRequest,
			"response"=>$this->exedra->httpResponse,
			"map"=> function() use($app) { return new \Exedra\Application\Map\Map(new \Exedra\Application\Map\Factory($app->loader));},
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception"),
			'path' => array('\Exedra\Application\Builder\Path', array($this->loader))
			// 'file'=> function() use($app) { return new \Exedra\Application\Builder\File($app->loader);}
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

				// \Exedra\Application\Map\Finding
				$finding = $this->map->find($request);
				$finding->addParameter($parameter);
			}

			// route not found.
			if(!$finding->success())
				return $this->throwFailedExecution($finding, $query, $parameter);

			$exe = new Execution\Exec($this, $finding);

			$this->exe = $exe;

			// save to the stack of execution.
			$this->executions[] = $exe;

			$execution = $finding->route->getParameter('execute');
			$execution = $this->registry->pattern->resolve($execution);

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
			// prioritieze the current $exe first.
			if(($this->exe && $failRoute = $this->exe->getFailRoute()) || $failRoute = $this->registry->getFailRoute())
			{
				if($this->exe)
					$this->exe->setFailRoute(null);
				else
					$this->setFailRoute(null);

				// set this false, so that it wont loop if later this fail route doesn't exists.
				return $this->execute($failRoute, array("exception"=>$e));
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
	protected function throwFailedExecution(\Exedra\Application\Map\Finding $finding, $query, array $parameter = array())
	{
		if($HTTPrequest = $finding->request)
		{
			$msg = 'Querying Request :'."\n";
			$msg .= 'Method : '.strtoupper($HTTPrequest->getMethod())."\n";
			$msg .= 'Request URI : '.$HTTPrequest->getUri()."\n";
			$msg .= 'Ajax : '.($HTTPrequest->isAjax() ? 'ya' : 'no');
		}
		else
		{
			$msg = 'Querying Route : '.$query;
		}

		return $this->exception->create('Route not found. '.$msg);
	}
}
?>