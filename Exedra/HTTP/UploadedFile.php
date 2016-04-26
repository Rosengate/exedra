<?php
namespace Exedra\Http;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\UploadedFileInterface
 */
class UploadedFile
{
	protected $name;

	protected $type;

	protected $path;

	protected $stream = null;

	protected $error;

	protected $size;

	public function __construct(array $file)
	{
		$this->name = $file['name'];

		$this->type = $file['type'];

		$this->path = $file['tmp_name'];

		$this->error = $file['error'];

		$this->size = $file['size'];
	}

	/**
	 * Create an array of \Exedra\Http\UploadedFile
	 * @param array files
	 * @return array
	 */
	public static function createFromGlobals(array $files = array())
	{
		$uploadedFiles = array();

		foreach($_FILES as $key => $file)
			$uploadedFiles[$key] - new static($file);

		return $uploadedFiles;
	}

	public function getStream()
	{
		if(!$this->stream)
			$this->stream = Stream::createFromPath($this->path);

		return $this->stream;
	}

	/**
	 * Move this file to the target path
	 * @param string targetPath
	 */
	public function moveTo($targetPath)
	{
		move_uploaded_file($this->path, $targetPath);
	}

	/**
	 * Get uploaded file size
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Get upload error
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Get file name
	 * @return string
	 */
	public function getClientFileName()
	{
		return $this->name;

	}

	public function getClientMediaType()
	{
		return $this->type;
	}
}