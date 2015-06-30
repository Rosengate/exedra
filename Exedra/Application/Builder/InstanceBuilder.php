<?php
namespace Exedra\Application\Builder;

Abstract Class InstanceBuilder
{
	/**
	 * Builder name.
	 * @var string
	 */
	protected $builderName;

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

	public function __construct(\Exedra\Application\Execution\Exec $exe, $module = null)
	{
		$this->exe = $exe;
		$this->loader = $exe->loader;
		$this->structure = $exe->app->structure;
		$this->module = $exe->getModule();

		// if the execution instance has this config.
		if($exe->config->has('namespaced_builder'))
			$this->isNamespaced = $exe->config->get('namespaced_builder');
	}

	/**
	 * Create the builder
	 * @param string className
	 * @param array constructorParam
	 * @return Object
	 */
	public function create($className, array $constructorParam = array())
	{
		$builderName = $this->builderName;

		## loader.
		// $path	= $this->structure->get($builderName,$className.".php",$this->module);
		$path = $className.'.php';

		## Exception : file not found.
		if(!$this->loader->has(array('structure'=> $builderName, 'path'=> $path)))
		{
			$structure = $this->structure->get($builderName);
			$path = $this->exe->app->getAppName().'/'.($this->exe->getModule()?$this->exe->getModule().'/':'').$structure.'/'.$path;
			$this->exe->exception->create("Unable to find file '".$path."' for ".$builderName." : ".$className.($this->module?" (module : ".$this->module.")":"").".");
		}

		$this->loader->loadOnce(array('structure'=> $builderName, 'path'=> $path));

		// namespace based builder.
		if($this->isNamespaced)
			$className = $this->exe->app->getAppname().'\\'.($this->exe->getModule() ? $this->exe->getModule().'\\' : '' ).$builderName.'\\'.$className;
		else
			$className		= $this->structure->getPattern($this->patternName,$className);

		## Exception : class name not found.
		if(!class_exists($className))
			$this->exe->exception->create("Class named '$className' does not exists in file ".$path);

		if(!is_object($className))
		{
			if($constructorParam)
			{
				$reflection	= new \ReflectionClass($className);
				$controller	= $reflection->newInstanceArgs($constructorParam);
			}
			else
			{
				$controller	= new $className;
			}

			$reflection	= new \ReflectionClass($controller);
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
			$this->exe->exception->create($reflection->getName()." : Method '$method' does not exists.");
		}

		return call_user_func_array(Array($controller,$method), $parameter);
	}
}