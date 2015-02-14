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

	public function __construct($baseDir = null, \Exedra\Application\Structure\Structure $structure = null)
	{
		$this->baseDir = !$baseDir ? null : trim($baseDir, '/');
		$this->structure = $structure;
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

		return trim($this->baseDir, '/').'/'.$path;
	}

	/**
	 * Required the file.
	 * @param mixed file
	 * - if string, will take it as purely path.
	 * - if array, will use the passed value as configuration options
	 * @param array data
	 * @return required file
	 */
	public function load($file,$data = null)
	{
		$file = is_array($file) ? $this->configure($file) : $file;
		$file = $this->refinePath($this->prefixPath($file));

		if(isset($loaded[$file])) return false;

		if(!file_exists($file))
			throw new \Exception("File not found : $file");

		if($data && is_array($data))
			extract($data);

		return require_once $file;
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
	 * Register autoload.
	 * @param string dir
	 */
	public function registerAutoload($dir)
	{
		// if by list
		if(is_array($dir))
		{
			foreach($dir as $d)
				$this->registerAutoload($d);

			return;
		}

		spl_autoload_register(function($class) use($dir)
		{
			$path			= $dir."/".$class.".php";
			$originalPath	= strtolower($path);

			## extract both class name and vendor from the called name.
			$explodes = explode("\\", $class, 2);
			if(count($explodes) > 1)
				 list($vendor,$class)	= $explodes;
			else
				list($vendor) = $explodes;

			## check the vendor based class.
			$class	= ucfirst($class);

			if($vendor == "Exedra")
				$path	= __DIR__."/".$class.".php";
			else
				$path	= rtrim($dir,"/")."/".$vendor."/".$class.".php";

			$path	= $this->refinePath($path);

			if(file_exists($path))
			{
				require_once $path;
			}
			else if(file_exists($this->refinePath($originalPath)))
			{
				require_once $this->refinePath($originalPath);
			}
		});
	}

	/**
	 * Get the content of file of the path
	 * @param string file name
	 * @return mixed file contents
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