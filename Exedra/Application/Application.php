<?php namespace Exedra\Application;

class Application extends Container
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
	 * @param string|array params [if string, expect app directory, else array of directories, and configuration]
	 * @param \Exedra\Exedra exedra instance
	 */
	public function __construct($params, \Exedra\Exedra $exedra = null)
	{
		$this->configure(is_array($params) ? $params : array('dir.app' => $params));

		// initialize properties
		$this->initiateProperties();

		// autoload the current folder
		$this->loader->autoload('', $this->config->get('namespace'));

		// register dependencies.
		$this->serviceRegistry();
	}

	/**
	 * Configure default variables
	 * @param array params
	 */
	protected function configure(array $params)
	{
		$this->config = new \Exedra\Application\Config;

		if(!isset($params['dir.app']))
			throw new \Exception('dir.app parameter is required, at least.');

		foreach($params as $key => $value)
			$this->config->set($key, $value);

		// root will be one level higher
		if(!isset($params['dir.root']))
			$this->config->set('dir.root', $params['dir.app'].'/..');

		// by default, set public dir on one level higher
		if(!isset($params['dir.public']))
			$this->config->set('dir.public', $params['dir.app'].'/../public');

		// take namespace from folder name
		if(!isset($params['namespace']))
		{
			$paths = explode('/', str_replace('\\', '/', $params['dir.app']));
			$this->config->set('namespace', ucwords(end($paths)));
		}
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

		$this->loader = new \Exedra\Loader($this->getDir(), $this->structure);
		
		$this->mapFactory = new \Exedra\Application\Map\Factory($this);
	}

	/**
	 * Register dependencies.
	 */
	protected function serviceRegistry()
	{
		$app = $this;

		$this->dependencies['services'] = array(
			'registry'=> array('\Exedra\Application\Registry', array($this)),
			'request' => function(){return \Exedra\HTTP\ServerRequest::createFromGlobals();},
			// 'response' => function(){return \Exedra\Application\Execution\Response::createEmptyResponse();},
			"map"=> function() use($app) { return $app->mapFactory->createLevel();},
			"url" => array("\Exedra\Application\Builder\Url", array($this)),
			"config"=> array("\Exedra\Application\Config"),
			"session"=> array("\Exedra\Application\Session\Session"),
			"exception"=> array("\Exedra\Application\Builder\Exception", array($this)),
			'path' => array('\Exedra\Application\Builder\Path', array($this->loader)),
			'middleware' => array('\Exedra\Application\Middleware\Registry', array($this))
			);
	}

	/**
	 * Get directory for app folder
	 * @param string|null path
	 * @return string
	 */
	public function getDir($path = null)
	{
		return $this->config->get('dir.app') . ($path ? '/' . $path : '');
	}

	/**
	 * Alias for getDir
	 * @param string|null path
	 * @return string
	 */
	public function getBaseDir($path = null)
	{
		return $this->getDir($path);
	}

	/**
	 * Get root directory
	 * @param string|null path
	 * @return string
	 */
	public function getRootDir($path = null)
	{
		return $this->config->get('dir.root') . ($path ? '/' . $path : '');
	}

	public function getNamespace()
	{
		return $this->config->get('namespace');
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
	 * @param string|array|\Exedra\HTTP\ServerRequest query
	 * @param array parameter
	 * @return \Exedra\Application\Execution\Exec
	 */
	public function execute($query, array $parameter = array(), \Exedra\HTTP\ServerRequest $request = null)
	{
		try
		{
			// expect it as route name
			if(is_string($query))
			{
				$finding = $this->map->findByName($query, $parameter, $request);
			}
			// expect it either \Exedra\HTTP\ServerRequest or array
			else
			{
				if($query instanceof \Exedra\HTTP\ServerRequest)
				{
					$request = $query;
				}
				else
				{
					$data = $query;
					$query = array();
					\Exedra\Functions\Arrays::initiateByNotation($query, $data);
					
					$request = \Exedra\HTTP\ServerRequest::createFromArray($query);
					// $request = new \Exedra\HTTP\ServerRequest($query);
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

			// $response = Execution\Resolver::resolve($execution($exe));
			$exe->response->setBody($execution($exe));

			// clear flash on every application execution (only if it has started).
			if(\Exedra\Application\Session\Session::hasStarted())
				$exe->flash->clear();
			
			return $exe;
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
	 * Individually dispatch this application with the given HTTP request
	 * @return \Exedra\HTTP\Response ?
	 */
	public function dispatch(\Exedra\HTTP\ServerRequest $request = null)
	{
		$request = $request ? : $this->request;

		$exe = $this->execute($request);

		$exe->response->sendHeader();

		echo $exe->response->getBody();
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
			$msg .= 'Request Path : '.$HTTPrequest->getUri()->getPath()."\n";
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