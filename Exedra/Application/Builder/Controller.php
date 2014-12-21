<?php
namespace Exedra\Application\Builder;

class Controller
{
	private $structure;
	private $loader;
	private $dir 			= null;
	protected $patternName	= "controller_name";
	private $namespaced		= false;

	public function __construct($exe, \Exedra\Application\Structure $structure, \Exedra\Application\Loader $loader,$dir = null)
	{
		$this->structure = $structure;
		$this->loader = $loader;
		$this->dir = $dir;
		$this->exe = $exe;
	}

	public function create($className,$constructorParam = null)
	{
		## loader.
		$path	= $this->structure->get("controller",$className.".php",$this->dir);

		## Exception : file not found.
		if(!file_exists($path))
			$this->exe->exception->create("Class file for ".$this->name." named '$className' does not exists.");

		$this->loader->load($path);

		## prepare class name by pattern.
		$className		= $this->structure->getPattern($this->patternName,$className);
		
		## Exception : class name not found.
		if(!class_exists($className))
			$this->exe->exception->create("Class named '$className' does not exists for controller '$className'");

		if(!is_object($className))
		{
			## if controller builder was namespaced, alter name.
			if($this->namespaced)
			{
				
			}

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
