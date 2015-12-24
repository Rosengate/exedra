<?php
namespace Exedra;

/**
 * General loader for exedra, available on every 3 major instance. Exedra, Application and Exec.
 */
class Loader
{
	/**
	 * Based directory this loader is based on.
	 * @var string
	 */
	protected $baseDir;

	/**
	 * Available only for application and exec instance.
	 * @var \Exedra\Application\Structure\Structure
	 */
	protected $structure = null;

	/**
	 * List of custom configuration
	 * @var array
	 */
	protected $configurations;

	/**
	 * List of autoloaded dirs and namespaces
	 * @var array
	 */
	protected $autoloadingRegistry = array();

	public function __construct($baseDir = null, \Exedra\Application\Structure\Structure $structure = null)
	{
		$this->baseDir = !$baseDir ? null : rtrim($baseDir, '/');
		$this->structure = $structure;
		$this->autoloadRegister();
	}

	/**
	 * Prefix path with the configured $baseDir
	 * @param string path
	 * @return string
	 */
	private function prefixPath($path)
	{
		if(!$this->baseDir === null)
			return $path;

		return rtrim($this->baseDir, '/').'/'.$path;
	}

	/**
	 * Required the file.
	 * @param mixed file
	 * - if string, will take it as purely path.
	 * - if array, will use the passed value as configuration options
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
	 * Abstract function for load and loadOnce
	 * @param mixed file
	 * @param array data
	 * @param boolean once
	 * @return required file
	 */
	protected function loadFile($file, $data, $once = false)
	{
		$file = is_array($file) ? $this->configure($file) : $file;

		$file = $this->refinePath($this->prefixPath($file));

		if(!file_exists($file))
			throw new \Exception("File not found : $file");

		extract($data);

		if($once)
			return require_once $file;
		else
			return require $file;

	}

	/**
	 * Add custom configuration to path parameter.
	 * @param string name
	 * @param callback callback (will pass path, and file[key]), requre the same string of path as return.
	 */
	protected function addConfiguration($name, $callback)
	{
		$this->configurations[$name] = $callback;
	}

	/**
	 * Get the configured path with the given options.
	 * @param array options
	 */
	protected function configure(array $options)
	{
		if(!isset($options['path']))
			throw new \Exception('Path parameter missing.');

		$path = $options['path'];

		foreach($options as $key=>$val)
		{
			switch($key)
			{
				case 'structure':
				$structure = $this->structure->get($val);

				// append this structure to the path.
				$path = $structure.'/'.$path;
				break;
			}
		}

		if(count($this->configurations) > 0)
		{
			foreach($this->configurations as $key=>$callback)
			{
				if(isset($file[$key]))
				{
					$path = $callback($path, $file[$key]);
				}
			}
		}

		return $path;
	}

	/**
	 * Check whether file exists.
	 * @param string path to file.
	 * @return boolean
	 */
	public function has($path)
	{
		$path = is_array($path) ? $this->configure($path) : $path;
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
		$this->autoloadingRegistry[] = array($basePath, $prefix, $relative);
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
		return $this->baseDir;
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
			$baseDir = $this->getBaseDir();

			foreach($this->autoloadingRegistry as $structs)
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
					$filename = $baseDir.DIRECTORY_SEPARATOR.$filename;

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
		$file = is_array($file) ? $this->configure($file) : $file;
		$file = $this->refinePath($this->prefixPath($file));

		if(!file_exists($file))
			throw new \Exception("File not found : $file");

		return file_get_contents($file);
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
		$path = is_array($path) ? $this->configure($path) : $path;
		$path = $this->refinePath($this->prefixPath($path));

		return $path;
	}

	/**
	 * Refine path, replace with the right directory separator.
	 * @param string path
	 * @return string
	 */
	private function refinePath($path)
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

	/**
	 * Short syntax checking for structure and path string.
	 * @param string file
	 * @return string
	 */
	public function isLoadable($file)
	{
		return strpos($file, ":") !== false;
	}
}

?>