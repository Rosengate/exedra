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
	protected $pathRegistry;

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
	 * Prefix path with the configured $basePath
	 * @param string path
	 * @return string
	 */
	private function prefixPath($path)
	{
		if(!$this->basePath === null)
			return $path;

		return rtrim($this->basePath, '/').'/'.$path;
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
		$file = $this->refinePath($this->prefixPath($file));

		if(!file_exists($file))
			throw new \Exedra\Exception\NotFoundException("File [$file] not found");

		extract($data);

		if($once)
			return require_once $file;
		else
			return require $file;

	}

	/**
	 * Check whether path registry exists.
	 * @param string path to file.
	 * @return boolean
	 */
	public function has($name)
	{
		return isset($this->pathRegistry[$name]);
	}

	/**
	 * Check whether file exists.
	 * @param string path to file.
	 * @return boolean
	 */
	public function isExists($path = null)
	{
		$path = $this->refinePath($this->prefixPath($path));

		return file_exists($path);
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
		$file = $this->refinePath($this->prefixPath($file));

		if(!file_exists($file))
			throw new \Exedra\Exception\NotFoundException("File [$file] not found");

		return file_get_contents($file);
	}

	/**
	 * Create file instance
	 * @return \Exedra\Application\Factory\File
	 */
	public function file($filename)
	{
		return new \Exedra\Application\Factory\File($this, $filename);
	}

	/**
	 * Get string based path
	 * @return string
	 */
	public function path($path = null)
	{
		return $this->path . ($path ? '/'.$path : '');
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
		$file = $this->buildPath($file);

		return file_put_contents($file, $contents);
	}

	/**
	 * Usable public function to help with building the path.
	 * @param mixed path
	 * @param string
	 */
	public function buildPath($path)
	{
		$path = $this->refinePath($this->prefixPath($path));

		return $path;
	}

	/**
	 * @param string name
	 * @param string path
	 */
	public function offsetSet($name, $path)
	{

	}

	/**
	 * Get a loader through a offset key
	 * @param string name
	 */
	public function offsetGet($name)
	{
		// create a loader by the same path and name.
		if(!isset($this->pathRegistry[$name]))
			$this->pathRegistry[$name] = new static($this->basePath.'/'.ltrim($name, '/\\'));

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
	 * Append a new path for the given name and path
	 * @param string name
	 * @param string path
	 */
	public function append($name, $path, $absolute = false)
	{
		$path = $absolute ? $path : $this->basePath.'/'.ltrim($path, '/\\');

		$this->pathRegistry[$name] = new static($path);

		return $this;
	}

	/**
	 * String castable
	 * @return string
	 */
	public function __toString()
	{
		return $this->basePath;
	}

	/**
	 * Refine path, replace with the right directory separator.
	 * @param string path
	 * @return string
	 */
	protected function refinePath($path)
	{
		switch(PHP_OS)
		{
			case "WINNT":
			return str_replace("/", DIRECTORY_SEPARATOR,$path);
			break;
			case "Linux":
			return str_replace("\\", DIRECTORY_SEPARATOR, $path);
			break;
			case "Darwin":
			return str_replace("\\", DIRECTORY_SEPARATOR, $path);
			break;
			default:
			return str_replace("\\", DIRECTORY_SEPARATOR, $path);
		}
	}
}

?>