<?php
namespace Exedra\Application\Structure;

class Structure
{
	protected $paths;
	protected $patterns;
	protected $characters;
	protected $basePath;

	public function __construct($basePath)
	{
		// application base path
		$this->setBasePath($basePath);

		// default path
		$this->set(array(
			"controller"	=>"controller",
			"model"			=>"model",
			"config"		=>"config",
			"view"			=>"view",
			"route"			=>"routes",
			"documents"		=>"documents",
			"middleware"	=>"middleware",
			"storage"		=>"storage"));

		$this->setCharacter('absolute', '@');

		$this->setPattern('controller_name', function($val)
		{
			return "Controller".str_replace(" ","",ucwords(str_replace("/", " ", $val)));
		});

		$this->setPattern('middleware_name', function($val)
		{
			return "Middleware".str_replace(" ", "", ucwords(str_replace("/", " ", $val)));
		});
	}

	private function refinePath($paths)
	{
		return implode("/",$paths);
	}

	/**
	 * Set base directory. basically this framework will default it to 'app'.
	 */
	public function setBasePath($path)
	{
		$this->basePath = $path != null ? $path : 'app';
	}


	/*public function getAppName()
	{
		return $this->appName;
	}*/

	## prefix, add just after the app name. suffix add at the end of structure value.
	/**
	 * Get the structure path.
	 * @param string name of the structure
	 * @param string additional suffix 
	 * @param string prefix before the suffix.
	 * @return string path.
	 */
	public function get($name,$suffix = null,$prefix = null)
	{
		$paths = array();
		$paths[] = $this->basePath;

		if($prefix)
		{
			foreach($prefix = explode("/",$prefix) as $no=>$p)
			{
				$paths[]	= $p;

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new \Exception("Structure : Directory for path ($temp) does not exist");
			}
		}

		if(!isset($this->paths[$name]))
			throw new \Exception("Structure '$name' does not exist");

		$paths[]	= $this->paths[$name];

		## Exception : directory for this path does not exists.
		if(!is_dir($temp = $this->refinePath($paths)))
			throw new \Exception("Structure : Directory for path ($temp) does not exist");

		if($suffix)
		{
			foreach($additionals = explode("/",$suffix) as $no=>$p)
			{
				$paths[]	= $p;

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new \Exception("Structure : Directory for path ($temp) does not exist");
			}
		}

		return $this->refinePath($paths);
	}

	/**
	 * Set the structure path
	 * @param mixed key
	 * @param mixed value
	 */
	public function set($key, $val = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
				$this->set($k, $v);

			return $this;
		}

		$this->paths[$key] = $val;

		return $this;
	}

	/**
	 * Get character.
	 * @param string key
	 * @return string character.
	 */
	public function getCharacter($key)
	{
		return $this->characters[$key];
	}

	public function setCharacter($key, $val)
	{
		$this->characters[$key] = $val;

		return $this;
	}

	/**
	 * Get pattern
	 * @param string pattern
	 * @param mixed val
	 * @return something 
	 */
	public function getPattern($pattern,$val)
	{
		if(!isset($this->patterns[$pattern]))
			throw new \Exception("Structure : pattern called $pattern does not exists.");

		return $this->patterns[$pattern]($val);
	}

	/**
	 * Create or set a pattern.
	 * @param string key
	 * @param callback callback
	 * @return this
	 */
	public function setPattern($key, $callback)
	{
		$this->patterns[$key] = $callback;

		return $this;
	}
}



?>