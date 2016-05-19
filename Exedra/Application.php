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

		$this->attributes['providers'] = new \Exedra\Provider\Registry($this);
		
		$this->attributes['config'] = new \Exedra\Application\Config;

		// default the namespace as [App]
		if(isset($params['namespace']))
			$this->namespace = $params['namespace'];
		
		$this->pathRegistry(is_array($params) ? $params : array('path.root' => $params));
		
		$this->serviceRegistry();
	}

	/**
	 * Configure default paths.
	 * root, app, public, routes
	 * @param array params
	 */
	protected function pathRegistry(array $params)
	{
		if(!isset($params['path.root']))
			throw new \Exedra\Exception\InvalidArgumentException('[path.root] parameter is required, at least.');

		$params['path.root'] = rtrim($params['path.root'], '/\\');

		$this->attributes['path'] = $path = new \Exedra\Path($params['path.root']);

		$path->register('root', $path); // recursive reference.

		$path->register('app', isset($params['path.app']) ? $params['path.app'] : $path->to('app'), true);

		$path->register('public', isset($params['path.public']) ? $params['path.public'] : $path->to('public'), true);

		$path->register('routes', isset($params['path.routes']) ? $params['path.routes'] : $path['app']->to('Routes'), true);

		// autoload the current app folder.
		$path['app']->autoloadPsr4($this->namespace, '');

		$this->attributes['loader'] = $path;
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
		$this->attributes['services']->register(array(
			'mapFactory' => function(){ return new \Exedra\Routing\Factory($this);},
			'execution' => array('\Exedra\Application\Execution\Registry', array('factories.execution.handlers')),
			'middleware' => array('\Exedra\Application\Middleware\Registry', array('factories.middleware.collection')),
			'request' => function(){ return \Exedra\Http\ServerRequest::createFromGlobals();},
			'map' => function() { return $this->mapFactory->createLevel();},
			'url' => function() { return new \Exedra\Application\Factory\Url($this->map, $this->request, $this->config->get('app.url', null), $this->config->get('asset.url', null));},
			'@session' => '\Exedra\Session\Session',
			'@flash' => array('\Exedra\Session\Flash', array('self.session')),
			'wizard' => array('\Exedra\Wizard\Manager', array('self'))
		));

		$this->attributes['factories']->register(array(
			'execution.exe' => '\Exedra\Application\Execution\Exec',
			'execution.handlers' => '\Exedra\Application\Execution\Handlers',
			'middleware.collection' => '\Exedra\Application\Middleware\Collection',
		));
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
		return $this->exec($this->map->findByRequest($request ? : $this->request));
	}

	/**
	 * Create the exec instance by given finding.
	 * @param \Exedra\Routing\Finding finding
	 * @return \Exedra\Application\Execution\Exec
	 *
	 * @throws \Exedra\Exception\RouteNotFoundException
	 */
	public function exec(\Exedra\Routing\Finding $finding)
	{
		if(!$finding->success())
			throw new \Exedra\Exception\RouteNotFoundException('Route is not found');

		return $this->create('execution.exe', array($this, $this->middleware, $finding));
	}

	/**
	 * Dispatch and return the response
	 * Handle uncaught \Exedra\Exception\Exception
	 * @param \Exedra\Http\ServerRequest|null
	 * @return \Exedra\Application\Execution\Response
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
			if($failRoute = $this->execution->getFailRoute())
			{
				$response = $this->execute($failRoute, array('exception' => $e), $request)
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
		// keep pinging the providers registry.
		$this->attributes['providers']->listen($type.'.'.$name);

		if(!$this->attributes[$type]->has($name))
		{
			if($this->attributes[$type]->has('@'.$name))
			{
				$registry = $this->attributes[$type]->get('@'.$name);
			}
			else
			{
				$isShared = false;

				if($type == 'callables' && ($this->attributes['services']->has($name) || $isShared = $this->attributes['services']->has('@'.$name)))
				{
					$service = $this->get($isShared ? '@'.$name : $name);

					$this->invokables[$name] = $service;

					if(is_callable($service))
						return call_user_func_array($service, $args);
				}

				throw new \Exedra\Exception\InvalidArgumentException('Unable to find the ['.$name.'] in the registered '.$type);
			}
		}
		else
		{
			$registry = $this->attributes[$type]->get($name);
		}

		return $this->resolve($registry, $args);
	}
}
