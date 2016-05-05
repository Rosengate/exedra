<?php namespace Exedra;

class Application extends \Exedra\Container\Container
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
	 * Application based loader
	 * @var \Exedra\Loader
	 */
	public $loader;

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
	public function __construct($params)
	{
		parent::__construct();

		// register dependencies.
		$this->serviceRegistry();

		$this->configure(is_array($params) ? $params : array('dir.app' => $params));

		$this->loader = new \Exedra\Loader($this->getDir());

		$this->loader->autoload('', $this->config->get('namespace'));
	}

	/**
	 * Configure default variables
	 * @param array params
	 */
	protected function configure(array $params)
	{
		if(!isset($params['dir.app']))
			throw new \Exedra\Exception\InvalidArgumentException('dir.app parameter is required, at least.');

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
		$this->execution->setFailRoute($routename);
	}

	/**
	 * Register dependencies.
	 */
	protected function serviceRegistry()
	{
		$this->dependencies['services']->register(array(
			'mapFactory' => function(){ return new \Exedra\Application\Map\Factory($this);},
			'execution' => array('\Exedra\Application\Execution\Registry', array('factories.executionHandlers')),
			'middleware' => array('\Exedra\Application\Middleware\Registry', array('factories.middlewares', 'factories.middlewareHandlers')),
			'request' => function(){ return \Exedra\Http\ServerRequest::createFromGlobals();},
			'map' => function() { return $this->mapFactory->createLevel();},
			'url' => array('\Exedra\Application\Factory\Url', array('self.map', 'self.request', 'self.config')),
			'config' => '\Exedra\Application\Config',
			'@session' => '\Exedra\Application\Session\Session',
			'@flash' => array('\Exedra\Application\Session\Flash', array('self.session')),
			'path' => array('\Exedra\Application\Factory\Path', array('self.loader'))
		));

		$this->dependencies['factories']->register(array(
			'exe' => '\Exedra\Application\Execution\Exec',
			'executionHandlers' => '\Exedra\Application\Execution\Handlers',
			'middlewares' => '\Exedra\Application\Middleware\Middlewares',
			'middlewareHandlers' => '\Exedra\Application\Middleware\Handlers'
		));
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

	/**
	 * Get application namespace
	 * @param string|null namespace
	 * @return string
	 */
	public function getNamespace($namespace = null)
	{
		return $this->config->get('namespace') . ($namespace ? '\\'.$namespace : '');
	}

	/**
	 * Get public directory
	 * @param string|null
	 * @return string
	 */
	public function getPublicDir($path = null)
	{
		return $this->config->get('dir.public') . ($path ? '/' . $path : '');
	}

	/**
	 * Get application execution registry
	 * @return \Exedra\Application\Registry
	 */
	public function getExecutionRegistry()
	{
		return $this->execution;
	}

	/**
	 * @return \Exedra\Application\Middleware\Registry
	 */
	public function getMiddlewareRegistry()
	{
		return $this->middleware;
	}

	/**
	 * @return \Exedra\Http\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Execute application with route name
	 * @param string|\Exedra\Http\ServerRequest query
	 * @param array parameter
	 * @return \Exedra\Application\Execution\Exec
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	public function execute($routeName, array $parameters = array(), \Exedra\Http\ServerRequest $request = null)
	{
		// expect it as route name
		if(!is_string($routeName))
			throw new \Exedra\Exception\InvalidArgumentException('Argument 1 must be [string]');
			
		return $this->exec($this->map->findByName($routeName, $parameters, $request));
	}

	/**
	 * Execute using http request
	 * @param \Exedra\Http\ServerRequest|null
	 * @return \Exedra\Application\Execution\Exec
	 */
	public function request(\Exedra\Http\ServerRequest $request = null)
	{
		return $this->exec($this->map->find($request ? : $this->request));
	}

	/**
	 * Create the exec instance by given finding.
	 * @param \Exedra\Application\Map\Finding finding
	 * @return \Exedra\Application\Execution\Exec
	 *
	 * @throws \Exedra\Exception\RouteNotFoundException
	 */
	public function exec(\Exedra\Application\Map\Finding $finding)
	{
		if(!$finding->success())
			throw new \Exedra\Exception\RouteNotFoundException('Route is not found');

		return $this->create('exe', array($this, $finding));
	}

	/**
	 * Dispatch and return the response
	 * @param \Exedra\Http\ServerRequest|null
	 * @return \Exedra\Application\Execution\Response
	 */
	public function respond(\Exedra\Http\ServerRequest $request = null)
	{
		try
		{
			$response = $this->request($request)
							->finalize()
							->getResponse();
		}
		catch(\Exedra\Exception\Exception $e)
		{
			if($failRoute = $this->execution->getFailRoute())
			{
				$response = $this->execute($failRoute, array('exception' => $e))
								->finalize()
								->getResponse();
				
				$this->setFailRoute(null);
			}
			else
			{
				$response = \Exedra\Application\Execution\Response::createEmptyResponse()
				->setStatus(404)
				->setBody($e->getMessage());
			}
		}

		return $response;
	}

	/**
	 * Dispatch and send the response
	 * Clear any flash
	 * @param \Exedra\Http\ServerRequest|null
	 */
	public function dispatch(\Exedra\Http\ServerRequest $request = null)
	{
		$response = $this->respond($request);

		if(\Exedra\Application\Session\Session::hasStarted())
			$this->flash->clear();

		return $response->send();
	}

	/**
	 * Run wizard
	 * @param string
	 */
	public function wizard($argv, $class = null)
	{
		if(!$class)
		{
			if($this->dependencies['factories']->has('wizard'))
				$wizard = $this->create('wizard', array($this));
			else
				$wizard = new \Exedra\Console\Wizard\Arcanist($this);
		}
		else
		{
			$wizard = new $class($this);
		}

		array_shift($argv);

		$wizard->run($argv);
	}

	/**
	 * Extended container solve method
	 * Search for shared symbol for sharable dependency
	 * @param string type
	 * @param string name
	 * @param array args
	 * @return mixed
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	protected function solve($type, $name, array $args = array())
	{
		if(!$this->dependencies[$type]->has($name))
		{
			if($this->dependencies[$type]->has('@'.$name))
				$registry = $this->dependencies[$type]->get('@'.$name);
			else
				throw new \Exedra\Exception\InvalidArgumentException('Unable to find the ['.$name.'] in the registered '.$type);
		}
		else
		{
			$registry = $this->dependencies[$type]->get($name);
		}

		return $this->resolve($registry, $args);
	}
}
?>