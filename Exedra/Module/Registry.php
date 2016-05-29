<?php
namespace Exedra\Module;

class Registry implements \ArrayAccess
{
	/**
	 * Base path of this registry
	 * Views, controller created will basically base on this path
	 * @var \Exedra\Path path
	 */
	protected $path;

	/**
	 * Application instance
	 * @var \Exedra\Application
	 */
	protected $app;

	/**
	 * Base namespace of this registry
	 * Module created will be base on this path
	 * @var string baseNamespace
	 */
	protected $baseNamespace;

	/**
	 * List of resolved module
	 * @var array modules
	 */
	protected $modules = array();

	/**
	 * List of module registry
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * List of module configures
	 * @var array configures
	 */
	protected $configures = array();

	public function __construct(\Exedra\Application $app, \Exedra\Path $path, $baseNamespace = null)
	{
		$this->app = $app;

		$this->path = $path;

		$this->baseNamespace = $baseNamespace;
	}

	/**
	 * Alias to register
	 * @param string name
	 * @param mixed resolve
	 */
	public function offsetSet($name, $registry)
	{
		$this->register($name, $registry);
	}

	/**
	 * Get application instance.
	 * @return \Exedra\Application
	 */
	public function getApp()
	{
		return $this->app;
	}

	public function offsetExists($key)
	{
		return isset($this->registry[$key]);
	}

	public function offsetUnset($key)
	{
		unset($this->registry[$key]);
	}

	/**
	 * Get base path of this registry
	 * Every module created will fall under this path
	 * @return \Exedra\Path
	 */
	public function getBasePath()
	{
		return $this->path;
	}

	/**
	 * Create default module.
	 * @param string namespace
	 * @return \Exedra\Module\Module
	 */
	protected function createModule($namespace, $class = null)
	{
		$path = $this->path->create(str_replace('\\', '/', $namespace));

		if($this->baseNamespace)
			$namespace = $this->baseNamespace.'\\'.$namespace;

		if($class)
			return new $class($this->app, $path, $namespace);
		else
			return $this->app->create('module', array($this->app, $path, $namespace));
	}

	/**
	 * Configure module
	 * Applied on module resolve
	 * If already resolved, applied on the spot.
	 * @param string name
	 * @param \Closure configure
	 */
	public function configure($name, \Closure $callback)
	{
		if(!isset($this->modules[$name]))
		{
			$this->configures[$name][] = $callback;

			return;
		}

		$callback($this->modules[$name]);
	}

	/**
	 * Register a module
	 * @param string name
	 * @param mixed
	 */
	public function register($name, $resolve)
	{
		if($resolve instanceof \Exedra\Module\Module)
			return $this->modules[$name] = $resolve;

		$this->registry[$name] = $resolve;
	}

	/**
	 * Get/resolve module
	 * @param string name
	 * @return \Exedra\Module\Module
	 */
	public function get($name)
	{
		if(isset($this->modules[$name]))
			return $this->modules[$name];
		
		if(!isset($this->registry[$name]))
		{
			$module = $this->createModule($name);
		}
		else
		{
			// resolve
			$registry = $this->registry[$name];

			// expect a class name
			if(is_string($registry))
			{
				$module = $this->createModule($name, $registry);
			}
			else if($registry instanceof \Closure)
			{
				$module = $registry($this->app, $name);
			}
			else
			{
				throw new \Exedra\Exception\Exception('Module registry must be either String or \Closure');
			}

			if(!$module instanceof \Exedra\Module\Module)
				throw new \Exedra\Exception\InvalidArgumentException('Resolve of module ['.$name.'] must be type of \Exedra\Module\Module');
		}

		// resolves with the configures.
		if(isset($this->configures[$name]))
		{
			foreach($this->configures[$name] as $callback)
				$callback($module);
		}

		return $this->modules[$name] = $module;
	}

	/**
	 * Alias to Get
	 * @param string name
	 * @return \Exedra\Module\Module
	 */
	public function offsetGet($name)
	{
		return $this->get($name);
	}
}

