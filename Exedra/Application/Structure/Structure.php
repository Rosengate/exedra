<?php
namespace Exedra\Application\Structure;

/**
 * A class to handle application structure
 */

class Structure
{
	/**
	 * List of structure paths
	 * @var array
	 */
	protected $paths = array();

	/**
	 * List of patterns like controller_name, and middleware_name
	 * @var array
	 */
	protected $patterns = array();

	/**
	 * List of characters, like absolute character, for routing.
	 * @var array
	 */
	protected $characters = array();

	public function __construct()
	{
		// Initiate default
		$this->add(array(
			"controller"	=>"Controller",
			"model"			=>"model",
			"config"		=>"config",
			"view"			=>"view",
			"routes"		=>"routes",
			"documents"		=>"documents",
			"middleware"	=>"Middleware",
			"storage"		=>"storage"));

		//  Absolute character
		$this->setCharacter('absolute', '@');

		// class name for controller.
		$this->setPattern('controller_name', function($val)
		{
			return "Controller".str_replace(" ","",ucwords(str_replace("/", " ", $val)));
		});

		// class name for middleware.
		$this->setPattern('middleware_name', function($val)
		{
			return "Middleware".str_replace(" ", "", ucwords(str_replace("/", " ", $val)));
		});
	}

	/**
	 * Get structure path
	 * @param string name
	 * @return string
	 */
	public function get($name)
	{
		if(!isset($this->paths[$name]))
			throw new \Exception("Structure '$name' does not exist");

		return $this->paths[$name];
	}

	## prefix, add just after the app name. suffix add at the end of structure value.
	/**
	 * DEPRECATED : NO LONGER USED
	 * Get the structure path.
	 * @param string name of the structure
	 * @param string additional suffix 
	 * @param string prefix before the suffix.
	 * @return string path.
	 */
	public function getOld($name,$suffix = null,$prefix = null)
	{
		$paths = array();
		// $paths[] = $this->basePath;

		if($prefix)
		{
			foreach($prefix = explode("/",$prefix) as $no=>$p)
			{
				$paths[]	= $p;/*

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new \Exception("Structure : Directory for path ($temp) does not exist");*/
			}
		}

		if(!isset($this->paths[$name]))
			throw new \Exception("Structure '$name' does not exist");

		$paths[]	= $this->paths[$name];

		## Exception : directory for this path does not exists.
		/*if(!is_dir($temp = $this->refinePath($paths)))
			throw new \Exception("Structure : Directory for path ($temp) does not exist");*/

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
	 * Add a structure (an alias to set())
	 * @param mixed key (if array, will recursively add by the loop)
	 * @param mixed structure value
	 * @return this
	 */
	public function add($key, $val = null)
	{
		return $this->set($key, $val);
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

	/**
	 * Set character
	 * @param string key
	 * @param mixed val
	 * @return this
	 */
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