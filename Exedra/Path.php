<?php
namespace Exedra;

/**
 * General loader for exedra, available on every 3 major instance. Exedra, Application and Exec.
 */
class Path implements \ArrayAccess
{
	/**
	 * Based directory this loader is based on.
	 * @var string
	 */
	protected $basePath;

	/**
	 * Registry of paths
	 * @var array
	 */
	protected $pathRegistry = array();

	/**
	 * List of autoloaded dirs and namespaces
	 * @var array
	 */
	protected $autoloadRegistry = array();

	public function __construct($basePath = null)
	{
		$this->basePath = !$basePath ? null : rtrim($basePath, '/\\');

		$this->autoloadRegister();
	}

	/**
	 * Required the file.
	 * @param string file
	 * @param array data
	 * @return required file
	 */
	public function load($file, array $data = array())
	{
		return $this->loadFile($file, $data, false);
	}

	/**
	 * Similar with load, but only require the file once.
	 * @param mixed file
	 * @param array data
	 * @return required file
	 */
	public function loadOnce($file, array $data = array())
	{
		return $this->loadFile($file, $data, true);
	}

	/**
	 * Do a buffered file inclusion
	 * @param string file
	 * @param array data
	 * @return string
	 */
	public function loadBuffered($file, array $data = array())
	{
		ob_start();

		$this->load($file, $data);

		return ob_get_clean();
	}

	/**
	 * Abstract function for load and loadOnce
	 * @param mixed file
	 * @param array data
	 * @param boolean once
	 * @return required file
	 */
	protected function loadFile($file, $data, $once = false)
	{
		$file = $this->basePath.'/'.ltrim($file, '/\\');

		if(!file_exists($file))
			throw new \Exedra\Exception\NotFoundException("File [$file] not found");

		extract($data);

		if($once)
			return require_once $file;
		else
			return require $file;

	}

	/**
	 * Whether given path exists
	 * @param string path to file.
	 * @return boolean
	 */
	public function has($path)
	{
		return file_exists($this->basePath.'/'.ltrim($path, '/\\'));
	}

	/**
	 * Alias to has()
	 * But with optional argument
	 * Which can also be used to check the base path existence
	 * @param string|null path
	 * @return boolean
	 */
	public function isExists($path = null)
	{
		return file_exists($this->basePath.($path ? '/'.ltrim($path, '/\\') : ''));
	}

	/**
	 * PSR-4 autoloader path register
	 * @param string basePath
	 * @param string prefix (optional), a namespace prefix
	 * @param boolean relative (optional, default : true), if false, will consider the basePath given as absolute.
	 */
	public function registerAutoload($basePath, $prefix = '', $relative = true)
	{
		$this->autoloadRegistry[] = array($basePath, $prefix, $relative);
	}

	/**
	 * Alias to registerAutoload
	 * @param string basePath
	 * @param string prefix (optional), a namespace prefix
	 * @param boolean relative (optional, default : true), if false, will consider the basePath given as absolute.
	 */
	public function autoload($basePath, $prefix = '', $relative = true)
	{
		return $this->registerAutoload($basePath, $prefix, $relative);
	}

	/**
	 * Get base directory this loader is based on
	 * @return string
	 */
	public function getBaseDir()
	{
		return $this->basePath;
	}

	/**
	 * Register autoloading
	 * @return null
	 */
	protected function autoloadRegister()
	{
		$loader = $this;

		spl_autoload_register(function($class) use($loader)
		{
			$basePath = $this->getBaseDir();

			foreach($this->autoloadRegistry as $structs)
			{
				$dir = $structs[0];
				$prefix = $structs[1];
				$relative = $structs[2];

				if($prefix != '' && strpos($class, $prefix) !== 0)
					continue;

				// remove prefix from path.
				$classDir = substr($class, strlen($prefix));

				$filename = $dir.DIRECTORY_SEPARATOR.(str_replace('\\', DIRECTORY_SEPARATOR, $classDir)).'.php';

				if($relative)
					$filename = $basePath.DIRECTORY_SEPARATOR.$filename;

				if(file_exists($filename))
					return require_once $filename;
			}
		});
	}

	/**
	 * Get the content of file of the path
	 * @param string file name
	 * @return mixed file contents
	 * @throws \Exception
	 */
	public function getContent($file)
	{
		$file = $this->basePath.'/'.ltrim($file, '/\\');

		if(!file_exists($file))
			throw new \Exedra\Exception\NotFoundException("File [$file] not found");

		return file_get_contents($file);
	}

	/**
	 * Create File instance
	 * @return \Exedra\Application\Factory\File
	 */
	public function file($filename)
	{
		return new \Exedra\Application\Factory\File($this, $filename);
	}

	/**
	 * Alias to create()
	 * @param string path
	 * @return \Exedra\Path
	 */
	public function path($path)
	{
		return new \Exedra\Path($this->basePath . ($path ? '/'.ltrim($path, '/\\') : ''));
	}

	/**
	 * Create \Exedra\Path based on given path
	 * @param string path
	 * @return \Exedra\Path
	 */
	public function create($path)
	{
		return new \Exedra\Path($this->basePath . ($path ? '/'.ltrim($path, '/\\') : ''));
	}

	/**
	 * Get string based path
	 * Except that the argument is required
	 * @param string
	 */
	public function to($path)
	{
		return $this->basePath.'/'.ltrim($path, '/\\');
	}

	/**
	 * Alias to getContent
	 * @param string file name
	 * @return string file contents
	 * @throws \Exception
	 */
	public function getContents($file)
	{
		return $this->getContent($file);
	}

	/**
	 * Put the content of file of the path
	 * @param string file name
	 * @return mixed file contents
	 */
	public function putContents($file, $contents)
	{
		$file = $this->basePath.'/'.ltrim($file, '/\\');

		return file_put_contents($file, $contents);
	}

	/**
	 * @param string name
	 * @param string path
	 */
	public function offsetSet($name, $path)
	{
		$path = $this->basePath.'/'.ltrim($path, '/\\');
		
		$this->pathRegistry[$name] = new static($path);
	}

	/**
	 * Get a loader through a offset key
	 * @param string name
	 */
	public function offsetGet($name)
	{
		// create a loader by the same path and name.
		if(!isset($this->pathRegistry[$name]))
			throw new \Exedra\Exception\NotFoundException('Path with registry ['.$name.'] does not exist.');

		return $this->pathRegistry[$name];
	}

	/**
	 * If loader name exists.
	 * @param string name
	 * @return boolean
	 */
	public function offsetExists($name)
	{
		return isset($this->pathRegistry[$name]);
	}

	/**
	 * Unset loader
	 * @param string name
	 */
	public function offsetUnset($name)
	{
		unset($this->pathRegistry[$name]);
	}

	/**
	 * Register a new path for the given name and path
	 * @param string name
	 * @param string path
	 */
	public function register($name, $path, $absolute = false)
	{
		$path = $absolute ? $path : $this->basePath.'/'.ltrim($path, '/\\');

		$this->pathRegistry[$name] = new static($path);

		return $this->pathRegistry[$name];
	}

	/**
	 * Has given name registered.
	 * @param string name
	 * @return boolean
	 */
	public function hasRegistry($name)
	{
		return isset($this->pathRegistry[$name]);
	}

	/**
	 * Get as string
	 * @return string
	 */
	public function toString()
	{
		return $this->basePath;
	}

	/**
	 * String castable
	 * @return string
	 */
	public function __toString()
	{
		return $this->basePath;
	}
}

?>