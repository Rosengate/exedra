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
	 * @var \Exedra\Application\Container
	 */
	public $container;

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
		$this->mapFactory = new \Exedra\Application\Map\Factory($this);
	}

	/**
	 * Register dependencies.
	 */
	protected function initiateContainer()
	{
		$app = $this;

		$this->container = new \Exedra\Application\Container(array(
			'registry'=> array('\Exedra\Application\Registry', array($this)),
			"request"=>$this->exedra->httpRequest,
			"response"=>$this->exedra->httpResponse,
			"map"=> function() use($app) { return $app->mapFactory->createLevel();},
			"url" => array("\Exedra\Application\Builder\Url", array($this)),
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception", array($this)),
			'path' => array('\Exedra\Application\Builder\Path', array($this->loader)),
			'middleware' => array('\Exedra\Application\Middleware\Registry', array($this))
			));
	}

	public function __get($property)
	{
		if($this->container->has($property))
		{
			$this->$property = $this->container->get($property);
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

			$execution = $finding->route->getProperty('execute');
			$execution = $this->registry->handlers->resolve($exe, $execution);

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
		catch(\Exedra\Application\Execution\Exception\Exception $exception)
		{
			$exe = $exception->exe;

			if($failRoute = $exe->getFailRoute())
				$exe->setFailRoute(null);
			else if($failRoute = $this->registry->getFailRoute())
				$this->setFailRoute(null);
			else
				return $this->exitWithMessage($exception->getMessage(), 'Execution Exception [Route : '.$exception->getRouteName().']');

			return $this->execute($failRoute, array('exception' => $exception));
		}
		catch(\Exception $exception)
		{
			if($failRoute = $this->registry->getFailRoute())
				$this->setFailRoute(null);
			else
				return $this->exitWithMessage($exception->getMessage(), get_class($exception));
			
			return $this->execute($failRoute, array('exception' => $exception));
		}
	}

	/**
	 * Exit script with given message and title
	 * @param string message
	 * @param string title
	 */
	protected function exitWithMessage($message, $title = null)
	{
		echo "<pre><hr>".($title ? "<u>".$title."</u>\n" : '').$message."<hr>";exit;
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
			$msg .= 'Request Path : '.$HTTPrequest->getUriPath()."\n";
			$msg .= 'Ajax : '.($HTTPrequest->isAjax() ? 'ya' : 'no');
		}
		else
		{
			$msg = 'Querying Route : '.$query;
		}

		return $this->exception->create('Route not found. '.$msg);
	}

	public function wizard($argv, $class = '\Exedra\Console\Wizard\Arcanist')
	{
		$wizard = new $class($this);

		array_shift($argv);

		$wizard->run($argv);
	}
}
?>