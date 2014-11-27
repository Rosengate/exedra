<?php
namespace Exedra\Application\Builder;

class View
{
	private $structure;
	private $loader;

	public function __construct($structure,$loader)
	{
		$this->structure	= $structure;
		$this->loader		= $loader;
	}

	public function create($path,$data = null)
	{
		$path	= $this->structure->get("view")."/".$path.".php";

		$view	= new \Exedra\Application\Response\View($path,$data,$this->loader);
		
		return $view;
	}
}