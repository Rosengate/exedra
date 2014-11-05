<?php
namespace Exedra\Application;

class Loader
{
	private $loaded;
	public function load($file)
	{
		if(isset($loaded[$file])) return false;

		return require_once $file;
	}
}



?>