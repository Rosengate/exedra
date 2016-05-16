<?php
namespace Exedra\Application\Factory;

/**
 * Simple class for object oriented based path.
 */
class File
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
	 * Load this file buffered.
	 * @param array data
	 * @return mixed
	 */
	public function loadBuffered(array $data = array())
	{
		return $this->basePath->loadBuffered($this->filename, $data);
	}

	/**
	 * Alias to getContent()
	 * @return mixed
	 */
	public function getContents()
	{
		if(!$this->isExists())
			throw new \Exedra\Exception\NotFoundException('File ['.$this->basePath.'/'.$this->filename.'] was not found.');
			
		return $this->basePath->getContents($this->filename);
	}


	/**
	 * Get spl info
	 * @return \SplFileInfo
	 */
	public function getSplInfo()
	{
		return new \SplFileInfo($this->toString());
	}

	/**
	 * Delete the file
	 */
	public function delete()
	{
		unlink($this->toString());
	}

	/**
	 * Put contents to the given path if it's file
	 * @param string data
	 * @param int flag file_put_contents flag
	 * @return mixed
	 */
	public function putContents($data = null, $flag = null, $context = null)
	{
		return $this->basePath->putContents($this->filename, $data, $flag, $context);
	}
}

?>