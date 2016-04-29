<?php
namespace Exedra\Application\Factory;

Abstract Class InstanceFactory
{
	/**
	 * Factory name.
	 * @var string
	 */
	protected $factoryName;

	/**
	 * Structure pattern to be used by \Exedra\Application\Structure\Structure
	 * @var string
	 */
	protected $patternName;

	/**
	 * Namespaced instance flag
	 * @var boolean
	 */
	protected $isNamespaced = true;

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		$this->loader = $exe->loader;
		
		$this->structure = $exe->app->structure;
		
		$this->module = $exe->getModule();

		// if the execution instance has this config.
		if($exe->config->has('namespaced_factory'))
			$this->isNamespaced = $exe->config->get('namespaced_factory');
	}

	/**
	 * Build class path
	 * @param string class
	 * @return string
	 */
	protected function buildClassPath($class)
	{
		$path = $this->exe->app->getBaseDir();

		if($module = $this->exe->getModule())
			$path .= '/'.$module;

		$path .= '/'.$this->factoryName;

		return $path . '/' .$class.'.php';
	}

	/**
	 * Build class name
	 * @param string class
	 * @return string
	 */
	protected function buildClassName($class)
	{
		$className = $this->exe->app->getNamespace();

		if($module = $this->exe->getModule())
			$className .= '\\'.$module;

		$className .= '\\'.ucwords($this->factoryName);

		$className .= '\\'.$class;

		return $className;
	}

	/**
	 * Create the factory
	 * @param string className
	 * @param array constructorParam
	 * @return Object
	 */
	public function create($className, array $args = array())
	{
		$factoryName = $this->factoryName;

		// loader.
		$path = $this->buildClassPath($className);

		// file not found
		if(!file_exists($path))
			throw new \Exedra\Exception\NotFoundException($factoryName.' ['.$path.'] does not exists');

		$className = $this->buildClassName($className);

		// class name does not exists in the given path.
		if(!class_exists($className))
			throw new \Exedra\Exception\NotFoundException('Class named ['.$className.'] does not exists in file '.$path);

		if(count($args))
		{
			$reflection	= new \ReflectionClass($className);

			$controller	= $reflection->newInstanceArgs($args);
		}
		else
		{
			$controller	= new $className;
		}

		return $controller;
	}

	/**
	 * Execute the instance.
	 * - if cname is string, create controller based on that string.
	 * - if cname is array, take first element as controller name, and second as construct parameters
	 * - else, expect it as the controller object.
	 * @param mixed cname
	 * @param string method
	 * @param array parameter
	 * @return execution
	 */
	public function execute($cname,$method,$parameter = Array())
	{
		if(is_string($cname))
			$controller	= $this->create($cname);
		else if(is_array($cname))
			$controller	= $this->create($cname[0],$cname[1]);
		else
			$controller	= $cname;

		if(!method_exists($controller, $method))
		{
			$reflection	= new \ReflectionClass($controller);

			throw new \Exedra\Exception\NotFoundException($reflection->getName()." : Method [$method] does not exists.");
		}

		return call_user_func_array(Array($controller,$method), $parameter);
	}
}