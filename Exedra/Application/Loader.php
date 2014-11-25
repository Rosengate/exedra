<?php
namespace Exedra\Application;

class Loader
{
	private $loaded;
	private $structure;

	public function __construct($structure)
	{
		$this->structure	= $structure;
	}

	public function loadStructure()
	{
		
	}

	public function load($file)
	{
		if(isset($loaded[$file])) return false;

		return require_once $file;
	}
}



?>