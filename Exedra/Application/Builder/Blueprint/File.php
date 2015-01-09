<?php
namespace Exedra\Application\Builder\Blueprint;

class File
{
	private $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function isExist()
	{
		return file_exists($this->path);
	}

	/**
	 * Load the given path.
	 */
	public function load($data = array())
	{
		if(count($data) > 0)
			extract($data);
		
		return require $this->path;
	}

	public function getContent()
	{
		if(!$this->isExist())
			return false;

		return file_get_contents($this->path);
	}
}



?>