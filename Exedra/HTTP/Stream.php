<?php
namespace Exedra\Http;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\UploadedFileInterface
 */
class Stream
{
	protected static $modes = array(
		'readable' => array('r', 'r+', 'w+', 'a+', 'x+', 'c+'),
		'writable' => array('r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+')
		);

	protected $meta = array();

	protected $resource = null;

	public function __construct($handle, $mode = 'r')
	{
		$this->attach($handle, $mode);
	}

	public static function createFromContents($contents, $mode = 'r+')
	{
		$handle = fopen('php://temp', $mode);

		fwrite($handle, $contents);

		return new self($handle);
	}

	public static function createFromPath($path, $mode = 'r+')
	{
		$handle = fopen($path, $mode);

		return new static($handle);
	}

	public function isAttached()
	{
		return is_resource($this->resource);
	}

	public function close()
	{
		if(!$this->resource)
			return null;

		fclose($this->resource);
	}

	public function attach($resource, $mode = 'r')
	{
		if(is_string($resource))
		{
			$resource = @fopen($resource, $mode);

			if(!$resource)
				throw new \InvalidArgumentException('Please provide string reference to the resource, or the resource itself.');
		}

		if(!is_resource($resource) || get_resource_type($resource) != 'stream')
			throw new \InvalidArgumentException('Please provide string reference to the resource, or the resource itself.', 1);

		$this->resource = $resource;

		$this->meta = stream_get_meta_data($resource);
	}

	public function detach()
	{
		$resource = $this->resource;

		$this->resource = null;

		return $resource;
	}

	public function getSize()
	{
		if(!$this->resource)
			return null;

		$fstat = fstat($this->resource);

		return $fstat['size'];
	}

	public function tell()
	{
		if(!$this->resource || ($position = ftell($this->resource)) === false)
			throw new \RuntimeException('Couldn\'t get stream position');

		return $position;
	}

	public function eof()
	{
		return $this->resource ? feof($this->resource) : true;
	}

	public function isSeekable()
	{
		if(!$this->resource)
			return false;

		$meta = stream_get_meta_data($this->resource);

		return $meta['seekable'];
	}

	public function seek($offset, $whence = SEEK_SET)
	{
		if(!$this->resource)
			throw new \RuntimeException('No resource is available');

		if(!$this->isSeekable())
			throw new \RuntimeException('Resource is not seekable');

		if(fseek($this->resource, $offset, $whence !== 0))
			throw new \RuntimeException('Failed to seek the resource');

		return $this;
	}

	public function rewind()
	{
		return $this->seek(0);
	}

	public function isRewritable()
	{
		if(!$this->resource)
			return false;

		return in_array($this->meta['mode'], self::$modes['writeable']);
	}

	public function write($contents)
	{
		if(!$this->resource)
			throw new \RuntimeException('No resource is available');

		if(!$this->isRewritable())
			throw new \RuntimeException('Resource is not writeable');

		if(fwrite($contents) === false)
			throw new \RuntimeException('Failed to write the resource');
	}

	public function isReadable()
	{
		if(!$this->resource)
			return false;

		return in_array($this->meta['mode'], self::$modes['readable']);
	}

	public function read($length)
	{
		if(!$this->resource)
			throw new \RuntimeException('No resource is available');

		if(!$this->isReadable())
			throw new \RuntimeException('Resource is not readable');

		if(($data = fread($this->resource, $length)) === false)
			throw new \RuntimeException('Failed to read the resource');

		return $data;
	}

	public function getContents()
	{
		if(!$this->resource)
			throw new \RuntimeException('No resource is available');

		if(($data = stream_get_contents($this->resource)) === false)
			throw new \RuntimeException('Failed to get the contents of the resource');

		return $data;
	}

	public function getMetadata($key = null)
	{
		return isset($this->meta[$key]) ? $this->meta[$key] : $this->meta;
	}

	public function toString()
	{
		if(!$this->resource)
			return '';

		return $this->rewind()->getContents();
	}

	public function __toString()
	{
		return $this->toString();
	}
}

?>