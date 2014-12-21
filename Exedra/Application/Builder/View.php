<?php
namespace Exedra\Application\Builder;

class View
{
	private $structure;
	private $loader;
	private $dir;

	public function __construct($exe, $structure,$loader,$dir = null)
	{
		$this->structure = $structure;
		$this->loader = $loader;
		$this->dir = $dir;
		$this->exe = $exe;
	}

	public function create($path,$data = null)
	{
		$path	= $path.".php";
		$path	= $this->structure->get("view",$path,$this->dir);
		
		if(!file_exists($path))
			$this->exe->exception->create("Unable to find view");

		$view	= new \Exedra\Application\Response\View($path,$data,$this->loader);
		
		return $view;
	}
}