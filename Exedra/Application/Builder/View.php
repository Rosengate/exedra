<?php
namespace Exedra\Application\Builder;

class View
{
	private $structure;
	private $loader;
	private $dir;

	public function __construct($structure,$loader,$dir = null)
	{
		$this->structure	= $structure;
		$this->loader		= $loader;
		$this->dir			= $dir;
	}

	public function create($path,$data = null)
	{
		$path	= $path.".php";
		$path	= $this->structure->get("view",$path,$this->dir);
		
		if(!file_exists($path))
			throw new \Exception("Unable to find view : $path");

		$view	= new \Exedra\Application\Response\View($path,$data,$this->loader);
		
		return $view;
	}
}