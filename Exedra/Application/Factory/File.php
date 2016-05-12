<?php
namespace Exedra\Application\Factory;

/**
 * Simple class for object oriented based path.
 */
class File extends \SplFileInfo
{
	/**
	 * Relative path of the file.
	 * @var string|array
	 */
	protected $filename;

	/**
	 * File base path
	 * @var \Exedra\Path
	 */
	protected $basePath;

	public function __construct(\Exedra\Path $basePath, $filename = null)
	{
		$this->basePath = $basePath;

		$this->filename = $filename;
	}

	/**
	 * Check whether this file exists or not.
	 * @return boolean
	 */
	public function isExists()
	{
		return $this->basePath->has($this->filename);
	}

	/**
	 * Cast into string
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Get full and usable path for this file.
	 * @return string
	 */
	public function toString()
	{
		return $this->basePath->to($this->filename);
	}

	/**
	 * Require this instance's file path extracted with the given data (optional)
	 * @param array data
	 * @return mixed
	 */
	public function load(array $data = array())
	{
		return $this->basePath->load($this->filename, $data);
	}

	/**
	 * Require this file content.
	 * @return mixed
	 */
	public function getContent()
	{
		if(!$this->isExists())
			return false;

		return $this->basePath->getContent($this->filename);
	}

	/**
	 * Alias to getContent()
	 * @return mixed
	 */
	public function getContents()
	{
		return $this->getContent();
	}


	/**
	 * Get spl info
	 * @return \splFileInfo
	 */
	public function getSplInfo()
	{
		return new \SplFileInfo($this->toString());
	}

	/**
	 * Put contents to the given path if it's file
	 * @param string data
	 * @return mixed
	 */
	public function putContents($data = null)
	{
		return $this->basePath->putContents($this->filename, $data);
	}
}

?>