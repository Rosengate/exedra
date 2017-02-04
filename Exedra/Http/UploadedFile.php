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

	public function __construct($name, $type, $tmp_name, $error, $size)
	{
		$this->name = $name;

		$this->type = $type;

		$this->path = $tmp_name;

		$this->error = $error;

		$this->size = $size;
	}

	/**
	 * Create an array of \Exedra\Http\UploadedFile
	 * @param array $files
	 * @return array
	 */
	public static function createFromGlobals(array $files = array())
	{
		return static::parseFiles($files);
	}

    /**
     * Normalize global $_FILES
     * @param array $files
     * @return array
     */
	protected static function parseFiles(array $files)
	{
		$normalizedFiles = array();

		foreach($files as $name => $file)
		{
			if(is_array($file) && !isset($file['tmp_name']))
				$normalizedFiles[$name] = static::parseFiles($file);
			else if(isset($file['tmp_name']) && is_array($file['tmp_name']))
				$normalizedFiles[$name] = static::normalizeFilesTree($file);
			else if(isset($file['tmp_name']))
				$normalizedFiles[$name] = new static(
					$file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']
					);
		}

		return $normalizedFiles;
	}

    /**
     * Normalize the tree recursively
     * thanks and credit to https://github.com/guzzle/psr7/blob/master/src/ServerRequest.php
     * @param array $file
     * @return array|static
     */
	protected static function normalizeFilesTree(array $file)
	{
		if(!is_array($file['tmp_name']))
			return new static(
				$file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']
				);

		$normalizedFiles = array();

		foreach(array_keys($file['tmp_name']) as $key)
			$normalizedFiles[$key] = static::normalizeFilesTree(array(
				'tmp_name' => $file['tmp_name'][$key],
				'name' => $file['name'][$key],
				'type' => $file['type'][$key],
				'error' => $file['error'][$key],
				'size' => $file['size'][$key]
				));

		return $normalizedFiles;
	}

	/**
	 * Get stream of the uploaded file
	 * @return \Exedra\Http\Stream
	 */
	public function getStream()
	{
		if(!$this->stream)
			$this->stream = Stream::createFromPath($this->path);

		return $this->stream;
	}

	/**
	 * Move this file to the target path
	 * @param string $targetPath
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

	/**
	 * Alias to getClientFileName()
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get file media type
	 * @return string
	 */
	public function getClientMediaType()
	{
		return $this->type;
	}

	/**
	 * Alias to getClientMediaType()
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
}