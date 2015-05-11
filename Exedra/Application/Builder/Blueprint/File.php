<?php
namespace Exedra\Application\Builder\Blueprint;

/**
 * Simple class for object oriented based file.
 */

class File
{
	/**
	 * Path of the file.
	 * @var string
	 */
	protected $path;

	public function __construct(\Exedra\Loader $loader, $path)
	{
		$this->loader = $loader;
		$this->path = $path;
	}

	/**
	 * Check whether this file exist or not.
	 * @return boolean
	 */
	public function isExist()
	{
		return $this->loader->has($this->path);
	}

	/**
	 * Require this instance's file path extracted with the given data (optional)
	 * @param array data
	 * @return mixed
	 */
	public function load(array $data = array())
	{
		return $this->loader->load($this->path, $data);
	}

	/**
	 * Require this file content.
	 * @return mixed
	 */
	public function getContent()
	{
		if(!$this->isExist())
			return false;

		return $this->loader->getContent($this->path);
	}
}



?>