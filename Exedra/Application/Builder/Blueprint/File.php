<?php
namespace Exedra\Application\Builder\Blueprint;

/**
 * Simple class for object oriented based file.
 */

class File
{
	private $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * Check whether this file exist or not.
	 * @return boolean
	 */
	public function isExist()
	{
		return file_exists($this->path);
	}

	/**
	 * Require this instance's file path extracted with the given data (optional)
	 * @param array data
	 * @param mixed
	 */
	public function load(array $data = array())
	{
		if(count($data) > 0)
			extract($data);
		
		return require $this->path;
	}

	/**
	 * Require this file content.
	 * @return mixed
	 */
	public function getContent()
	{
		if(!$this->isExist())
			return false;

		return file_get_contents($this->path);
	}
}



?>