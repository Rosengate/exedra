<?php
namespace Exedra\Application\Builder;

class Model
{
	private $app;
	private $structure;

	public function __construct(\Exedra\Application\Loader $loader)
	{
		$this->loader 		= $loader;
		$this->structure 	= $this->loader->structure;
	}

	public function get($className,$params = Array())
	{
		$path	= $this->structure->get('model',$className.'.php');

		## load model;
		$this->loader->load($path);

		$className	= refine_path($className);
		$className	= "\\".$this->structure->getAppName()."\Model\\$className";

		if($params)
		{
			$reflection	= new \ReflectionClass($className);
			$model		= $reflection->newInstanceArgs($params);
		}
		else
		{
			$model	= new $className;
		}

		return $model;
	}
}

?>