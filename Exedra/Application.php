<?php namespace Exedra;

class Application extends \Exedra\Application\Container
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
		$this->structure = new \Exedra\Application\Structure\Structure;

		$this->loader = new \Exedra\Loader($this->getDir(), $this->structure);
	}

	/**
	 * Register dependencies.
	 */
	protected function serviceRegistry()
	{
		$app = $this;

		$this->dependencies['services'] = array(
			'mapFactory' => function(){ return new \Exedra\Application\Map\Factory($this);},
			'registry'=> array('\Exedra\Application\Registry', array($this)),
			'request' => function(){ return \Exedra\Http\ServerRequest::createFromGlobals();},
			"map"=> function() { return $this->mapFactory->createLevel();},
			// "url" => array("\Exedra\Application\Builder\Url", array($this)),
			'url' => function() { return new \Exedra\Application\Builder\Url($this->map, $this->request, $this->config);},
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
	 * @param string|array|\Exedra\Http\ServerRequest query
	 * @param array parameter
	 * @return \Exedra\Application\Execution\Exec
	 */
	public function execute($query, array $parameter = array(), \Exedra\Http\ServerRequest $request = null)
	{
		try
		{
			// expect it as route name
			if(is_string($query))
			{
				$finding = $this->map->findByName($query, $parameter, $request);
			}
			// expect it either \Exedra\Http\ServerRequest or array
			else
			{
				if($query instanceof \Exedra\Http\ServerRequest)
				{
					$request = $query;
				}
				else
				{
					$data = $query;
					$query = array();
					\Exedra\Functions\Arrays::initiateByNotation($query, $data);
					
					$request = \Exedra\Http\ServerRequest::createFromArray($query);
					// $request = new \Exedra\Http\ServerRequest($query);
				}

				// \Exedra\Application\Map\Finding
				$finding = $this->map->find($request);
				$finding->addParameter($parameter);
			}

			// route not found.
			if(!$finding->success())
				return $this->throwFailedExecution($finding, $query, $parameter);

			$exe = new \Exedra\Application\Execution\Exec($this, $finding);

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
	 * Individually dispatch this application with the given Http request
	 * @return \Exedra\Http\Response ?
	 */
	public function dispatch(\Exedra\Http\ServerRequest $request = null)
	{
		$request = $request ? : $this->request;

		$exe = $this->execute($request);

		$body = $exe->response->getBody();

		$response = $exe->response;
		
		// recursively check if body is truly not another execution instance
		// if it is, retrieve both true body and http response, until the final
		while(true)
		{
			if($body instanceof \Exedra\Application\Execution\Exec)
			{
				$response = $body->response;

				$body = $body->response->getBody();
			}
			else
			{
				break;
			}
		}

		$response->sendHeader();

		echo $body;
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
		if($httpRequest = $finding->request)
		{
			$msg = 'Querying Request :'."\n";
			$msg .= 'Method : '.strtoupper($httpRequest->getMethod())."\n";
			$msg .= 'Request Path : '.$httpRequest->getUri()->getPath()."\n";
			$msg .= 'Ajax : '.($httpRequest->isAjax() ? 'ya' : 'no');
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