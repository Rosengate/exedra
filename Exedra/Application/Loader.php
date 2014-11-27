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

	public function load($file,$data = null)
	{
		if(isset($loaded[$file])) return false;

		if($data && is_array($data))
			extract($data);

		return require_once $file;
	}
}



?>