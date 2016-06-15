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
	 * List of module filters
	 * @var array filters
	 */
	protected $filters = array();

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
			return new $class($this->app, $namespace, $path);
		else
			return $this->app->create('module', array($this->app, $namespace, $path));
	}

	/**
	 * Configure module
	 * Applied on module resolve
	 * If already resolved, applied on the spot.
	 * @param string name
	 * @param \Closure configure
	 */
	public function configure($name, \Closure $filter)
	{
		if(!isset($this->modules[$name]))
		{
			$this->filters[$name][] = $filter;

			return;
		}

		$filter($this->modules[$name]);
	}

	/**
	 * Register a module
	 * @param string name assumed as the namespace
	 * @param mixed
	 */
	public function register($name, $resolve)
	{
		$this->registry[$name] = $resolve;
	}

	/**
	 * Get or resolve and configure module
	 * - If there's no registry for the module, it'll do a default instantiation
	 * - Else, if it's an object, and an instanceof \Exedra\Module\Module. It's good.
	 * - Else, if the registry is a string. It'll be expected as class name of a type of \Exedra\Module\Module
	 * - Else, if it's closure, it'll expect the returned one a type of \Exedra\Module\Module
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

			if(is_object($registry) && $registry instanceof \Exedra\Module\Module)
			{
				$module = $registry;
			}
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
				throw new \Exedra\Exception\Exception('Module registry must be either String, \Closure, or type of \Exedra\Module\Module');
			}

			if(!$module instanceof \Exedra\Module\Module)
				throw new \Exedra\Exception\InvalidArgumentException('Resolve of module ['.$name.'] must be type of \Exedra\Module\Module');
		}

		// resolves with the filters.
		if(isset($this->filters[$name]))
		{
			foreach($this->filters[$name] as $callback)
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

