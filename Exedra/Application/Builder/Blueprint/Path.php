<?php
namespace Exedra\Application\Builder\Blueprint;

/**
 * Simple class for object oriented based path.
 */

class Path
{
	/**
	 * Path of the file.
	 * @var string|array
	 */
	protected $path;

	public function __construct(\Exedra\Loader $loader, $path)
	{
		$this->loader = $loader;
		$this->path = $path;
	}

	/**
	 * Check whether this file exists or not.
	 * @return boolean
	 */
	public function isExists()
	{
		return $this->loader->has($this->path);
	}

	/**
	 * Magic function as if this is used for string.
	 * @return string
	 */
	public function __toString()
	{
		return $this->asString();
	}

	/**
	 * Get full and usable path for this file.
	 * @return string
	 */
	public function toString()
	{
		return $this->loader->buildPath($this->path);
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
		if(!$this->isExists())
			return false;

		return $this->loader->getContent($this->path);
	}
}

?>