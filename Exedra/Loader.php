<?php
namespace Exedra;

/**
 * General loader for exedra, available on every 3 major instance. Exedra, Application and Exec.
 */
class Loader
{
	/**
	 * Path is prefixed with dirPrefix depending on which instance it's based on.
	 */
	protected $dirPrefix;

	/**
	 * Available only for application and exec instance.
	 * \Exedra\Application\Structure\Structure
	 */
	protected $structure;

	/**
	 * List of custom configuration
	 */
	protected $configurations;

	public function __construct($dirPrefix = null, \Exedra\Application\Structure\Structure $structure = null)
	{
		$this->dirPrefix = !$dirPrefix ? null : trim($dirPrefix, '/');

		if($structure)
			$this->structure = $structure;
	}

	/**
	 * Prefix path with the configured dirPrefix
	 * @param string path
	 * @return string
	 */
	private function prefixPath($path)
	{
		if(!$this->dirPrefix === null)
			return $path;

		return trim($this->dirPrefix, '/').'/'.$path;
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
	 * Add custom configuration to path.
	 * @param string name
	 * @param callback callback (will pass path, and file[key]), requre the same string of path as return.
	 */
	private function addConfiguration($name, $callback)
	{
		$this->configurations[$name] = $callback;
	}

	private function configure(array $file)
	{
		if(!isset($file['path']))
			throw new \Exception('Path parameter missing.');

		$path = $file['path'];

		foreach($file as $key=>$val)
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
	 * File exists.
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
	 * @return file content
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
	 */
	public function isLoadable($file)
	{
		return strpos($file, ":") !== false;
	}
}

?>