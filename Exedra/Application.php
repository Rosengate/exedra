<?php namespace Exedra;

class Application extends \Exedra\Container\Container
{
	protected $namespace = 'App';

	/**
	 * Create a new application
	 * @param string|array params [if string, expect root directory, else array of directories, and configuration]
	 * @param \Exedra\Exedra exedra instance
	 */
	public function __construct($params)
	{
		parent::__construct();

		$this->services['provider'] = new \Exedra\Provider\Registry($this);
		
		// default the namespace as [App]
		if(isset($params['namespace']))
			$this->namespace = $params['namespace'];
		
		$this->setUpPath(is_array($params) ? $params : array('path.root' => $params));
		
		$this->setUp();
	}

	/**
	 * Configure default paths.
	 * root, app, public, routes
	 * @param array params
	 */
	protected function setUpPath(array $params)
	{
		if(!isset($params['path.root']))
			throw new \Exedra\Exception\InvalidArgumentException('[path.root] parameter is required, at least.');

		$params['path.root'] = rtrim($params['path.root'], '/\\');

		$this->services['path'] = $path = new \Exedra\Path($params['path.root']);

		$path->register('root', $path); // recursive reference.

		$path->register('app', isset($params['path.app']) ? $params['path.app'] : $path->to('app'), true);

		$path->register('public', isset($params['path.public']) ? $params['path.public'] : $path->to('public'), true);

		$path->register('routes', isset($params['path.routes']) ? $params['path.routes'] : $path['app']->to('Routes'), true);

		// autoload the current app folder.
		$path['app']->autoloadPsr4($this->namespace, '');

		$this->services['loader'] = $path;
	}

	/**
	 * Alias for above method.
	 * @param string routename
	 */
	public function setFailRoute($routename)
	{
		$this->runtime->setFailRoute($routename);
	}

	/**
	 * Setup dependency registry.
	 */
	protected function setUp()
	{
		$this->services['service']->register(array(
			'config' => '\Exedra\Config',
			'routing.factory' => function(){ return new \Exedra\Routing\Factory((string) $this->path['routes']);},
			'map' => function() { return $this['routing.factory']->createLevel();},
			'runtime' => array('\Exedra\Runtime\Registry', array('factory.runtime.handlers')),
			'middleware' => array('\Exedra\Middleware\Registry', array('self.map')),
			'request' => function(){ return \Exedra\Http\ServerRequest::createFromGlobals();},
			'url' => function() { return new \Exedra\Factory\Url($this->map, $this->request, $this->config->get('app.url', null), $this->config->get('asset.url', null));},
			'@session' => '\Exedra\Session\Session',
			'@flash' => array('\Exedra\Session\Flash', array('self.session')),
			'wizard' => array('\Exedra\Wizard\Manager', array('self')),
			'@module' => function(){
				return new \Exedra\Module\Registry($this, $this->path['app'], $this->getNamespace());
			}
		));

		$this->services['factory']->register(array(
			'runtime.exe' => '\Exedra\Runtime\Exe',
			'runtime.handlers' => '\Exedra\Runtime\Handlers',
			'module' => '\Exedra\Module\Module',
		));

		$this->services['service']->on('module', function(\Exedra\Module\Registry $registry)
		{
			$registry->register('Application', '\Exedra\Module\Application');
		});
	}

	/**
	 * Get directory for app folder
	 * @param string|null path
	 * @return string
	 */
	public function getDir($path = null)
	{
		return (string) $this->path['app'] . ($path ? '/' . $path : '');
	}

	/**
	 * Alias for getDir
	 * @param string|null path
	 * @return string
	 */
	public function getBaseDir($path = null)
	{
		return (string) $this->path['app'] . ($path ? '/' . $path : '');
	}

	/**
	 * Get root directory
	 * @param string|null path
	 * @return string
	 */
	public function getRootDir($path = null)
	{
		return (string) $this->path;
	}

	/**
	 * Get application namespace
	 * @param string|null namespace
	 * @return string
	 */
	public function getNamespace($namespace = null)
	{
		return $this->namespace . ($namespace ? '\\'.$namespace : '');
	}

	/**
	 * Get public directory
	 * @param string|null
	 * @return string
	 */
	public function getPublicDir($path = null)
	{
		return (string) $this->path['public'] . ($path ? '/' . $path : '');
	}

	/**
	 * Get application runtime registry
	 * @return \Exedra\Runtime\Registry
	 */
	public function getRuntimeRegistry()
	{
		return $this->runtime;
	}

	/**
	 * @return \Exedra\Middleware\Registry
	 */
	public function getMiddlewareRegistry()
	{
		return $this->middleware;
	}

	/**
	 * @return \Exedra\Http\ServerRequest
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Execute application with route name
	 * @param string|\Exedra\Http\ServerRequest query
	 * @param array parameter
	 * @return \Exedra\Runtime\Exe
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
	 * @return \Exedra\Runtime\Exe
	 */
	public function request(\Exedra\Http\ServerRequest $request = null)
	{
		return $this->exec($this->map->findByRequest($request ? : $this->request));
	}

	/**
	 * Create the exec instance by given finding.
	 * @param \Exedra\Routing\Finding finding
	 * @return \Exedra\Runtime\Exe
	 *
	 * @throws \Exedra\Exception\RouteNotFoundException
	 */
	public function exec(\Exedra\Routing\Finding $finding)
	{
		if(!$finding->isSuccess())
			throw new \Exedra\Exception\RouteNotFoundException('Route is not found');

		return $this->create('runtime.exe', array($this, $this->middleware, $finding));
	}

	/**
	 * Dispatch and return the response
	 * Handle uncaught \Exedra\Exception\Exception
	 * @param \Exedra\Http\ServerRequest|null
	 * @return \Exedra\Runtime\Response
	 */
	public function respond(\Exedra\Http\ServerRequest $request)
	{
		try
		{
			$response = $this->request($request)
							->finalize()
							->getResponse();
		}
		catch(\Exedra\Exception\Exception $e)
		{
			if($failRoute = $this->runtime->getFailRoute())
			{
				$response = $this->execute($failRoute, array('exception' => $e), $request)
								->finalize()
								->getResponse();
				
				$this->setFailRoute(null);
			}
			else
			{
				$response = \Exedra\Runtime\Response::createEmptyResponse()
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
		$response = $this->respond($request ? : $this->request);

		$response->send();
	}

	/**
	 * Listen to the console arguments.
	 * @param string
	 */
	public function wizard(array $arguments)
	{
		// shift out file name
		array_shift($arguments);
		
		return $this->wizard->listen($arguments);
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
		// keep pinging the provider registry.
		$this->services['provider']->listen($type.'.'.$name);

		if(!$this->services[$type]->has($name))
		{
			if($this->services[$type]->has('@'.$name))
			{
				$registry = $this->services[$type]->get('@'.$name);
			}
			else
			{
				$isShared = false;

				if($type == 'callable' && ($this->services['service']->has($name) || $isShared = $this->services['service']->has('@'.$name)))
				{
					$service = $this->get($isShared ? '@'.$name : $name);

					$this->invokables[$name] = $service;

					if(is_callable($service))
						return call_user_func_array($service, $args);
				}

				throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Unable to find ['.$name.'] in the registered '.$type);
			}
		}
		else
		{
			$registry = $this->services[$type]->get($name);
		}

		return $this->filter($type, $name, $this->resolve($name, $registry, $args));
	}
}
