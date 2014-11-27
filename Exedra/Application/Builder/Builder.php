<?php
namespace Exedra\Application\Builder;

Abstract Class Builder
{
	private $structure;

	public function __construct(\Exedra\Application\Structure $structure, \Exedra\Application\Loader $loader)
	{
		$this->structure	= $structure;
		$this->loader		= $loader;
	}

	public function create($cname,$constructorParam = null)
	{
		## loader.
		$path	= $this->structure->get($this->name,$cname.".php");

		## Exception : file not found.
		if(!file_exists($path)) throw new \Exception("Class file for ".$this->name." named '$cname' does not exists.");

		$this->loader->load($path);

		## prepare class name by pattern.
		$className		= $this->structure->getPattern($this->patternName,$cname);
		
		## Exception : class name not found.
		if(!class_exists($className)) throw new \Exception("Class named '$className' does not exists for ".$this->name." '$cname'");

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
			throw new \Exception("Method ($method) does not exists.");

		return call_user_func_array(Array($controller,$method), $parameter);
	}
}



?>